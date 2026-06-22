// Shared region scheme for the Leaflet choropleth maps. Keyed by the GeoJSON's
// adm1_psgc region code so any map can color/group provinces consistently with
// the DOST scheme (NIR groups Negros + Siquijor; Sulu sits under Region IX;
// BARMM is not a selectable region — it has no DOST PSTD provinces).
//
// Keep this in sync with the backend Province::REGIONS and Data/provinceGeography.js.

export const NIR_CODE = 1800000000; // Negros Island Region (PSGC Region XVIII)

// GeoJSON province name (adm2_en) → canonical DB / display name.
export const GEO_TO_DB = {
    'Cebu':                              'Cebu Province',
    'Cotabato':                          'Cotabato (North)',
    'Samar':                             'Samar (Western Samar)',
    'Dinagat Islands':                   'Dinagat Island',
    'City of Isabela (Not a Province)':  'Isabela City',
};

// Non-province polygons in the basemap we never render as provinces.
// City of Isabela is technically "Not a Province" in the GeoJSON but is a real
// geographic entity in Region IX that should appear on the map.
export const SKIP = (name) => !name
    || name.includes('NCR,')
    || (name.includes('Not a Province') && !name.startsWith('City of Isabela'));

// adm1_psgc overrides so the basemap matches the DOST region scheme rather than
// the GeoJSON's PSGC vintage.
export const REGION_CODE_OVERRIDE = {
    'Negros Occidental': NIR_CODE,   // → NIR
    'Negros Oriental':   NIR_CODE,   // → NIR
    'Siquijor':          NIR_CODE,   // → NIR
    'Sulu':              900000000,  // BARMM → Region IX
};

// Full label per region code.
export const REGION_LABELS = {
    100000000:  'Region I — Ilocos',
    200000000:  'Region II — Cagayan Valley',
    300000000:  'Region III — Central Luzon',
    400000000:  'Region IV-A — CALABARZON',
    1700000000: 'Region IV-B — MIMAROPA',
    500000000:  'Region V — Bicol',
    600000000:  'Region VI — Western Visayas',
    700000000:  'Region VII — Central Visayas',
    800000000:  'Region VIII — Eastern Visayas',
    [NIR_CODE]: 'NIR — Negros Island Region',
    900000000:  'Region IX — Zamboanga Peninsula',
    1000000000: 'Region X — Northern Mindanao',
    1100000000: 'Region XI — Davao Region',
    1200000000: 'Region XII — SOCCSKSARGEN',
    1400000000: 'CAR — Cordillera',
    1600000000: 'Region XIII — Caraga',
    1300000000: 'NCR — National Capital Region',
};

// Short label (no descriptive suffix) for compact UI.
export const REGION_SHORT = {
    100000000:  'Region I',
    200000000:  'Region II',
    300000000:  'Region III',
    400000000:  'Region IV-A',
    1700000000: 'Region IV-B',
    500000000:  'Region V',
    600000000:  'Region VI',
    700000000:  'Region VII',
    800000000:  'Region VIII',
    [NIR_CODE]: 'NIR',
    900000000:  'Region IX',
    1000000000: 'Region X',
    1100000000: 'Region XI',
    1200000000: 'Region XII',
    1400000000: 'CAR',
    1600000000: 'Region XIII',
    1300000000: 'NCR',
};

export const REGION_TO_ISLAND = {
    1300000000: 'luzon',   // NCR
    1400000000: 'luzon',   // CAR
    100000000:  'luzon',   // Region I
    200000000:  'luzon',   // Region II
    300000000:  'luzon',   // Region III
    400000000:  'luzon',   // Region IV-A
    1700000000: 'luzon',   // Region IV-B
    500000000:  'luzon',   // Region V
    600000000:  'visayas', // Region VI
    700000000:  'visayas', // Region VII
    800000000:  'visayas', // Region VIII
    [NIR_CODE]: 'visayas', // NIR
    900000000:  'mindanao',// Region IX
    1000000000: 'mindanao',// Region X
    1100000000: 'mindanao',// Region XI
    1200000000: 'mindanao',// Region XII
    1600000000: 'mindanao',// Region XIII
    1900000000: 'mindanao',// BARMM (basemap only; not a selectable DOST region)
};

export const ISLAND_LABELS = { luzon: 'Luzon', visayas: 'Visayas', mindanao: 'Mindanao' };

// Distinct color per region, grouped into island hue families so the three
// island groups read as colour families at a glance.
export const REGION_COLORS = {
    // Luzon — blues / cyans / teals
    1300000000: '#6366f1', // NCR        indigo
    1400000000: '#0ea5e9', // CAR        sky
    100000000:  '#3b82f6', // Region I   blue
    200000000:  '#2563eb', // Region II  blue-600
    300000000:  '#06b6d4', // Region III cyan
    400000000:  '#0891b2', // Region IV-A cyan-600
    1700000000: '#14b8a6', // Region IV-B teal
    500000000:  '#0d9488', // Region V   teal-600
    // Visayas — greens / limes
    600000000:  '#84cc16', // Region VI  lime
    700000000:  '#22c55e', // Region VII green
    800000000:  '#16a34a', // Region VIII green-600
    [NIR_CODE]: '#65a30d', // NIR        lime-600
    // Mindanao — warm / purple
    900000000:  '#f59e0b', // Region IX  amber
    1000000000: '#f97316', // Region X   orange
    1100000000: '#ef4444', // Region XI  red
    1200000000: '#e11d48', // Region XII rose
    1600000000: '#a855f7', // Region XIII purple
};

export const ISLANDS = [
    { value: 'luzon',    label: 'Luzon'    },
    { value: 'visayas',  label: 'Visayas'  },
    { value: 'mindanao', label: 'Mindanao' },
];

// Region codes in display order (grouped by island), filtered to labelled regions.
export const REGION_ORDER = [
    1300000000, 1400000000, 100000000, 200000000, 300000000, 400000000, 1700000000, 500000000,
    600000000, 700000000, 800000000, NIR_CODE,
    900000000, 1000000000, 1100000000, 1200000000, 1600000000,
];

export const effectiveRegionCode = (name, rawCode) => REGION_CODE_OVERRIDE[name] ?? rawCode;
export const regionColor = (code) => REGION_COLORS[code] ?? '#cbd5e1';
export const regionLabel = (code) => REGION_LABELS[code] ?? `Region ${code}`;
export const regionShort = (code) => REGION_SHORT[code] ?? regionLabel(code);
export const islandOf    = (code) => REGION_TO_ISLAND[code] ?? null;
