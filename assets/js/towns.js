// assets/js/towns.js
// Laguna towns and cities (approximate town centres) plus today's date in the Philippines.
// Kept in its own tiny file so the register page can load it without pulling in all of db.js.
// db.js imports TOWN_COORDS from here, so there is still only one list.

export const TOWN_COORDS = {
  "Alaminos": [14.0639, 121.2461],
  "Bay": [14.1833, 121.2833],
  "Biñan": [14.3333, 121.0833],
  "Cabuyao": [14.2724, 121.1251],
  "Calamba": [14.2117, 121.1653],
  "Calauan": [14.145, 121.3171],
  "Cavinti": [14.2433, 121.5096],
  "Famy": [14.4386, 121.4492],
  "Kalayaan": [14.3253, 121.4608],
  "Liliw": [14.1286, 121.4339],
  "Los Baños": [14.1699, 121.2415],
  "Luisiana": [14.1839, 121.5103],
  "Lumban": [14.2989, 121.4631],
  "Mabitac": [14.4297, 121.4261],
  "Magdalena": [14.2, 121.4333],
  "Majayjay": [14.1469, 121.4736],
  "Nagcarlan": [14.1364, 121.4153],
  "Paete": [14.3647, 121.4836],
  "Pagsanjan": [14.2736, 121.4547],
  "Pakil": [14.3786, 121.4781],
  "Pangil": [14.4042, 121.465],
  "Pila": [14.2333, 121.3667],
  "Rizal": [14.1111, 121.3939],
  "San Pablo City": [14.0689, 121.3256],
  "San Pedro": [14.3595, 121.0473],
  "Santa Cruz": [14.2781, 121.4161],
  "Santa Maria": [14.4678, 121.4283],
  "Santa Rosa": [14.3122, 121.1114],
  "Siniloan": [14.4169, 121.4486],
  "Victoria": [14.2242, 121.3306]
};

// Today in the Philippines, as YYYY-MM-DD
export function todayISO() {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).format(new Date());
}