import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import { GoogleGenAI } from "@google/genai";

dotenv.config();

const app = express();

app.use(cors());
app.use(express.json({ limit: "100kb" }));

const API_KEY = (process.env.GEMINI_API_KEY || "").trim();

if (!API_KEY) {
    console.warn("WARNING: GEMINI_API_KEY is missing. Add GEMINI_API_KEY=your_key to the .env file.");
}

// Keys created in Google AI Studio use the normal Gemini API (default).
// Only set GEMINI_USE_VERTEX=true in .env if you really use a Vertex AI key.
const useVertex = process.env.GEMINI_USE_VERTEX === "true";

const ai = new GoogleGenAI(
    useVertex
        ? { vertexai: true, apiKey: API_KEY }
        : { apiKey: API_KEY }
);

// Model can be changed without editing code: add GEMINI_MODEL=... to .env
const MODEL = process.env.GEMINI_MODEL || "gemini-3.8-flash";

console.log(`Gemini mode: ${useVertex ? "Vertex AI (express key)" : "Google AI Studio"} | model: ${MODEL}`);

const SYSTEM_PROMPT =
    "You are the AgriTrack Assistant, a friendly helper inside the AgriTrack website. " +
    "AgriTrack helps Filipino farmers monitor crops, check weather, manage harvests, find market locations, and get farming recommendations. " +
    "Answer questions about farming, crops, pests, planting and harvest timing, weather planning, and how to use AgriTrack. " +
    "Keep answers short, practical and easy to understand. Use simple English, and reply in Filipino/Tagalog if the user writes in Filipino. " +
    "If you are unsure, say so instead of guessing. Never ask for or repeat passwords or personal data.";

// Very small rate limit: 15 requests per minute per IP
const hits = new Map();
function rateLimited(ip) {
    const now = Date.now();
    const recent = (hits.get(ip) || []).filter((t) => t > now - 60000);
    recent.push(now);
    hits.set(ip, recent);
    return recent.length > 15;
}

// Build the Gemini "contents" array from the request.
// Accepts either { messages: [{role, text}, ...] } or the old { message: "..." }
function buildContents(body) {
    const contents = [];

    if (Array.isArray(body.messages)) {
        for (const m of body.messages.slice(-12)) {
            const role = m && m.role === "model" ? "model" : "user";
            const text = String((m && m.text) || "").trim().slice(0, 2000);
            if (!text) continue;
            if (contents.length === 0 && role === "model") continue; // must start with user
            contents.push({ role, parts: [{ text }] });
        }
    }

    if (contents.length === 0 && body.message) {
        contents.push({ role: "user", parts: [{ text: String(body.message).slice(0, 2000) }] });
    }

    return contents;
}

// Gemini AI endpoint
app.post("/api/chat", async (req, res) => {
    try {
        if (rateLimited(req.ip)) {
            return res.status(429).json({ error: "Too many messages. Please wait a moment and try again." });
        }

        const contents = buildContents(req.body || {});

        if (contents.length === 0) {
            return res.status(400).json({ error: "Message is required" });
        }

        const started = Date.now();

        const baseConfig = {
            systemInstruction: SYSTEM_PROMPT + " Keep replies under 120 words unless the user asks for detail.",
            temperature: 0.6,
            maxOutputTokens: 600
        };

        let response;
        try {
            // thinkingBudget: 0 = no "thinking" step, so the answer starts much faster
            response = await ai.models.generateContent({
                model: MODEL,
                contents,
                config: { ...baseConfig, thinkingConfig: { thinkingBudget: 0 } }
            });
        } catch (e) {
            // some models do not accept the thinking setting: retry once without it
            console.warn("First attempt failed, retrying without the thinking setting:", e && e.message ? e.message.slice(0, 300) : e);
            if (e && (e.status === 429 || e.status === 403)) {
                throw e; // quota / permission problems will not be fixed by a retry
            }
            response = await ai.models.generateContent({ model: MODEL, contents, config: baseConfig });
        }

        console.log(`Gemini replied in ${Date.now() - started} ms`);

        const reply = (response.text || "").trim() ||
            "Sorry, I couldn't come up with an answer for that. Could you rephrase it?";

        res.json({ reply });

    } catch (error) {
        console.error("Gemini API Error:", error);

        res.status(500).json({
            error: "Failed to communicate with Gemini AI"
        });
    }
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`AgriTrack Gemini server running on port ${PORT}`);
});
