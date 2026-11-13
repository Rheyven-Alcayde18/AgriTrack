import dotenv from "dotenv";
import { GoogleGenAI } from "@google/genai";

dotenv.config();

const key = (process.env.GEMINI_API_KEY || "").trim();

console.log("-----------------------------------------");
console.log(
    key
        ? `Key found in .env: yes (${key.length} characters, starts with "${key.slice(0, 3)}")`
        : "Key found in .env: NO  ->  .env must contain a line like GEMINI_API_KEY=your_key"
);
console.log("-----------------------------------------");

const ai = new GoogleGenAI({ apiKey: key });

const models = [
    "gemini-3.8-flash",
    "gemini-3.5-flash-lite"
];

for (const model of models) {
    const started = Date.now();
    try {
        const response = await ai.models.generateContent({
            model,
            contents: "Say hello in 5 words."
        });
        console.log(`OK    ${model}  (${Date.now() - started} ms): ${response.text.trim()}`);
    } catch (e) {
        const msg = String(e && e.message ? e.message : e).replace(/\s+/g, " ").slice(0, 220);
        console.log(`FAIL  ${model}  status ${e && e.status}: ${msg}`);
    }
}

console.log("-----------------------------------------");
console.log("Done. Copy everything above and send it to Claude.");
