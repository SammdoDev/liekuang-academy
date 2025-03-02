// Import Firebase
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getDatabase, ref, push, onValue } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js";

// Konfigurasi Firebase (ganti dengan milikmu)
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
    apiKey: "AIzaSyABhGlWoZT6mCjYVA-JfOosDIGZrD9LEuE",
    authDomain: "liekuang-academy.firebaseapp.com",
    databaseURL: "https://liekuang-academy-default-rtdb.firebaseio.com",
    projectId: "liekuang-academy",
    storageBucket: "liekuang-academy.firebasestorage.app",
    messagingSenderId: "915155201372",
    appId: "1:915155201372:web:0ef48f8c125641c14f2599",
    measurementId: "G-QJTBF5KXFM"
  };
// Inisialisasi Firebase
const app = initializeApp(firebaseConfig);
const database = getDatabase(app);
const dbRef = ref(database, "users");

// Fungsi untuk menambah data
export function tambahData(nama) {
    if (nama) {
        push(dbRef, { nama: nama });
    }
}

// Fungsi untuk menampilkan data secara real-time
export function listenData(updateCallback) {
    onValue(dbRef, (snapshot) => {
        let data = [];
        snapshot.forEach((childSnapshot) => {
            data.push(childSnapshot.val().nama);
        });
        updateCallback(data);
    });
}
