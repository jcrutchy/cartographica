export async function fetchPlayerProfile() {
    return dummyPlayerProfile; // replace with real fetch later
    // return fetch("/api/player/profile").then(r => r.json());
}



export async function fetchProfile() {
    return { name: "Guest" };
}
