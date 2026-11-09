// assets/js/sample-data.js
// Demo records. Day numbers are offsets from today, so the demo never goes stale.
// The admin 'Load sample data' button writes these into Firebase.

export const DEFAULT_MARKETS = {
    "bay-pm": {
        "name": "Bay Public Market",
        "short": "Bay PM",
        "type": "public",
        "town": "Bay, Laguna",
        "lat": 14.185,
        "lon": 121.285,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "05:00",
        "to": "16:00",
        "prices": {
            "tomato": 38,
            "eggplant": 42,
            "corn": 13
        },
        "note": "Closest market. Retail buyers and resellers."
    },
    "lb-pm": {
        "name": "Los Baños Public Market",
        "short": "Los Baños PM",
        "type": "public",
        "town": "Los Baños, Laguna",
        "lat": 14.1699,
        "lon": 121.2415,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "05:00",
        "to": "17:00",
        "prices": {
            "tomato": 40,
            "eggplant": 44,
            "corn": 14
        },
        "note": "Steady demand from the town and nearby campuses."
    },
    "calauan-pm": {
        "name": "Calauan Public Market",
        "short": "Calauan PM",
        "type": "public",
        "town": "Calauan, Laguna",
        "lat": 14.145,
        "lon": 121.3171,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "04:30",
        "to": "15:00",
        "prices": {
            "tomato": 36,
            "eggplant": 40,
            "corn": 13
        },
        "note": "Busiest in the early morning."
    },
    "sanpablo-pm": {
        "name": "San Pablo City Public Market",
        "short": "San Pablo PM",
        "type": "public",
        "town": "San Pablo City",
        "lat": 14.0689,
        "lon": 121.3256,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "04:00",
        "to": "18:00",
        "prices": {
            "tomato": 44,
            "eggplant": 48,
            "corn": 15
        },
        "note": "Larger market with higher prices and more buyers."
    },
    "calauan-coop": {
        "name": "Calauan Farmers Cooperative Station",
        "short": "Calauan Coop",
        "type": "coop",
        "town": "Calauan, Laguna",
        "lat": 14.15,
        "lon": 121.305,
        "days": [
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "07:00",
        "to": "16:00",
        "prices": {
            "rice": 21,
            "corn": 15
        },
        "note": "Buys palay and corn from members. Bring your ID."
    },
    "pila-rice": {
        "name": "Pila Rice Buying Station",
        "short": "Pila Rice",
        "type": "coop",
        "town": "Pila, Laguna",
        "lat": 14.2333,
        "lon": 121.3667,
        "days": [
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "07:00",
        "to": "17:00",
        "prices": {
            "rice": 22
        },
        "note": "Rice only. Best palay price nearby."
    },
    "stacruz-hub": {
        "name": "Santa Cruz Wholesale Trading Post",
        "short": "Sta. Cruz Hub",
        "type": "wholesale",
        "town": "Santa Cruz, Laguna",
        "lat": 14.2781,
        "lon": 121.4161,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "20:00",
        "to": "05:00",
        "prices": {
            "tomato": 46,
            "eggplant": 50,
            "corn": 16,
            "rice": 21
        },
        "note": "Overnight trading. Bulk loads only."
    },
    "calamba-hub": {
        "name": "Calamba Wholesale Vegetable Hub",
        "short": "Calamba Hub",
        "type": "wholesale",
        "town": "Calamba, Laguna",
        "lat": 14.2116,
        "lon": 121.1653,
        "days": [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ],
        "from": "21:00",
        "to": "06:00",
        "prices": {
            "tomato": 48,
            "eggplant": 52,
            "corn": 16
        },
        "note": "Top vegetable prices, but a longer haul."
    }
};

export const SAMPLE_FARMERS = [{"name":"Maria Santos","email":"maria.santos@example.com","phone":"0917 123 4501","municipality":"Bay","barangay":"Santo Domingo","farm_size":4.5,"status":"active","joined":-210,"lastActive":0,"crops":[{"name":"Tomato","field":"Field A","area":2,"planted":-70,"harvest":4,"moisture":60},{"name":"Eggplant","field":"Field B","area":2.5,"planted":-14,"harvest":95,"moisture":28}],"activities":[{"d":0,"text":"Logged soil moisture reading for Field B"},{"d":-2,"text":"Marked Field B watering as done"},{"d":-5,"text":"Compared market prices for tomato"},{"d":-14,"text":"Registered eggplant crop in Field B"}],"harvests":[{"d":-75,"crop":"Tomato","yield_t":0.9,"price":38},{"d":-130,"crop":"Eggplant","yield_t":0.7,"price":42}],"markets":["bay-pm","calamba-hub"]},{"name":"Juan Dela Cruz","email":"juan.delacruz@example.com","phone":"0918 555 2210","municipality":"Calauan","barangay":"Dayap","farm_size":8,"status":"active","joined":-320,"lastActive":-1,"crops":[{"name":"Rice","field":"Field 1","area":5,"planted":-104,"harvest":16,"moisture":60},{"name":"Corn","field":"Field 2","area":3,"planted":-49,"harvest":61,"moisture":60}],"activities":[{"d":-1,"text":"Viewed harvest plan for rice"},{"d":-3,"text":"Applied fertilizer to Field 2"},{"d":-9,"text":"Saved Pila Rice Buying Station"},{"d":-30,"text":"Updated rice harvest date"}],"harvests":[{"d":-140,"crop":"Rice","yield_t":3.6,"price":19},{"d":-260,"crop":"Corn","yield_t":2.1,"price":13}],"markets":["calauan-coop","pila-rice","calauan-pm"]},{"name":"Ana Reyes","email":"ana.reyes@example.com","phone":"0920 777 3382","municipality":"Los Baños","barangay":"Bayog","farm_size":2.5,"status":"active","joined":-95,"lastActive":-2,"crops":[{"name":"Eggplant","field":"Plot 1","area":1.5,"planted":-40,"harvest":55,"moisture":60},{"name":"Tomato","field":"Plot 2","area":1,"planted":-62,"harvest":9,"moisture":60}],"activities":[{"d":-2,"text":"Checked weather forecast"},{"d":-6,"text":"Marked tomato prep tasks as done"},{"d":-40,"text":"Registered eggplant crop in Plot 1"}],"harvests":[{"d":-50,"crop":"Tomato","yield_t":0.6,"price":40}],"markets":["lb-pm"]},{"name":"Pedro Garcia","email":"pedro.garcia@example.com","phone":"0919 404 6615","municipality":"Pila","barangay":"Labuin","farm_size":6,"status":"active","joined":-180,"lastActive":-6,"crops":[{"name":"Rice","field":"North field","area":6,"planted":-95,"harvest":27,"moisture":60}],"activities":[{"d":-6,"text":"Viewed Pila Rice Buying Station"},{"d":-12,"text":"Updated rice progress"}],"harvests":[{"d":-120,"crop":"Rice","yield_t":4.4,"price":20},{"d":-240,"crop":"Rice","yield_t":4.1,"price":19}],"markets":["pila-rice","stacruz-hub"]},{"name":"Luisa Mendoza","email":"luisa.mendoza@example.com","phone":"0921 880 1927","municipality":"San Pablo City","barangay":"San Gregorio","farm_size":3,"status":"active","joined":-60,"lastActive":-1,"crops":[{"name":"Corn","field":"Lot A","area":2,"planted":-30,"harvest":75,"moisture":60},{"name":"Eggplant","field":"Lot B","area":1,"planted":-8,"harvest":100,"moisture":28}],"activities":[{"d":-1,"text":"Saved San Pablo City Public Market"},{"d":-4,"text":"Watered Lot B"},{"d":-8,"text":"Registered eggplant crop in Lot B"}],"harvests":[],"markets":["sanpablo-pm"]},{"name":"Roberto Aquino","email":"roberto.aquino@example.com","phone":"0915 220 7743","municipality":"Bay","barangay":"Maitim","farm_size":5.5,"status":"active","joined":-270,"lastActive":0,"crops":[{"name":"Corn","field":"Field C","area":3.5,"planted":-80,"harvest":28,"moisture":60},{"name":"Tomato","field":"Field D","area":2,"planted":-66,"harvest":-1,"moisture":60}],"activities":[{"d":0,"text":"Opened recommendations"},{"d":-1,"text":"Compared market prices for tomato"},{"d":-7,"text":"Applied fertilizer to Field C"}],"harvests":[{"d":-90,"crop":"Tomato","yield_t":1.4,"price":41},{"d":-200,"crop":"Corn","yield_t":2.8,"price":14},{"d":-290,"crop":"Tomato","yield_t":1.1,"price":37}],"markets":["bay-pm","lb-pm","calamba-hub"]},{"name":"Carmen Villanueva","email":"carmen.villanueva@example.com","phone":"0922 331 5098","municipality":"Calauan","barangay":"Lamot 1","farm_size":1.5,"status":"inactive","joined":-400,"lastActive":-75,"crops":[{"name":"Eggplant","field":"Back lot","area":1.5,"planted":-150,"harvest":-60,"moisture":60,"harvestedOn":-60}],"activities":[{"d":-75,"text":"Marked eggplant as harvested"},{"d":-110,"text":"Viewed weather forecast"}],"harvests":[{"d":-62,"crop":"Eggplant","yield_t":0.8,"price":43},{"d":-230,"crop":"Eggplant","yield_t":0.6,"price":41}],"markets":["calauan-pm"]},{"name":"Eduardo Ramos","email":"eduardo.ramos@example.com","phone":"0916 909 4412","municipality":"Santa Cruz","barangay":"Patimbao","farm_size":7,"status":"inactive","joined":-330,"lastActive":-120,"crops":[{"name":"Rice","field":"Main field","area":7,"planted":-220,"harvest":-110,"moisture":60,"harvestedOn":-110}],"activities":[{"d":-120,"text":"Marked rice as harvested"}],"harvests":[{"d":-112,"crop":"Rice","yield_t":5.2,"price":20}],"markets":["stacruz-hub"]},{"name":"Grace Bautista","email":"grace.bautista@example.com","phone":"0917 662 1840","municipality":"Calauan","barangay":"Santo Tomas","farm_size":2,"status":"active","joined":-3,"lastActive":0,"crops":[{"name":"Tomato","field":"Plot 1","area":2,"planted":-2,"harvest":68,"moisture":60}],"activities":[{"d":0,"text":"Registered tomato crop in Plot 1"},{"d":-3,"text":"Created an account"}],"harvests":[],"markets":["calauan-pm"]},{"name":"Mark Soriano","email":"mark.soriano@example.com","phone":"0926 118 4457","municipality":"Los Baños","barangay":"Malinta","farm_size":3.5,"status":"active","joined":-12,"lastActive":-1,"crops":[{"name":"Corn","field":"Upper field","area":3.5,"planted":-10,"harvest":95,"moisture":60}],"activities":[{"d":-1,"text":"Checked weather forecast"},{"d":-10,"text":"Registered corn crop in Upper field"},{"d":-12,"text":"Created an account"}],"harvests":[],"markets":["lb-pm"]},{"name":"Teresa Navarro","email":"teresa.navarro@example.com","phone":"0928 305 9921","municipality":"San Pablo City","barangay":"Santa Monica","farm_size":1,"status":"active","joined":-25,"lastActive":-20,"crops":[],"activities":[{"d":-20,"text":"Viewed market locations"},{"d":-25,"text":"Created an account"}],"harvests":[],"markets":[]},{"name":"Ramon Castillo","email":"ramon.castillo@example.com","phone":"0919 770 3366","municipality":"Bay","barangay":"Tagumpay","farm_size":4,"status":"active","joined":-41,"lastActive":-2,"crops":[{"name":"Rice","field":"Field 1","area":3,"planted":-38,"harvest":82,"moisture":60},{"name":"Eggplant","field":"Field 2","area":1,"planted":-20,"harvest":70,"moisture":28}],"activities":[{"d":-2,"text":"Logged low soil moisture in Field 2"},{"d":-20,"text":"Registered eggplant crop in Field 2"},{"d":-38,"text":"Registered rice crop in Field 1"}],"harvests":[],"markets":["bay-pm","pila-rice"]}];

export const SAMPLE_MY_CROPS = [{"name":"Rice","field":"Field A","area":5,"planted":-105,"harvest":15,"moisture":82,"yield_t":4,"price":20},{"name":"Corn","field":"Field B","area":3.5,"planted":-50,"harvest":60,"moisture":61,"yield_t":2.4,"price":14},{"name":"Tomato","field":"Field C","area":2,"planted":-71,"harvest":3,"moisture":54,"yield_t":1.2,"price":40},{"name":"Eggplant","field":"Field D","area":2,"planted":-15,"harvest":93,"moisture":28,"yield_t":0.8,"price":45}];

export const SAMPLE_MY_HARVESTS = [{"d":-99,"crop":"Eggplant","field":"Field D","yield_t":0.7,"price":42},{"d":-140,"crop":"Tomato","field":"Field C","yield_t":0.9,"price":38},{"d":-144,"crop":"Rice","field":"Field A","yield_t":3.6,"price":19}];