import "./bootstrap";
import { Notyf } from "notyf";
import { Chart, registerables } from "chart.js";
import Alpine from "alpinejs";
import Swal from "sweetalert2";
import intersect from "@alpinejs/intersect";
import rangeSlider from "range-slider-input";
import L from "leaflet";
import "trix";
import Select from './select';

Alpine.plugin(intersect);
Chart.register(...registerables);

window.Select = Select;

window.Alpine = Alpine;
Alpine.start();

window.L = L;
window.Swal = Swal;
window.rangeSlider = rangeSlider;
window.notyf = new Notyf({
    duration: 2500,
    position: { x: "right", y: "top" },
});
window.Chart = Chart;

window.confirmDelete = function (form) {
    Swal.fire({
        title: "Yakin hapus data ini?",
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
        background: document.documentElement.classList.contains("dark")
            ? "#1f2937"
            : "#fff",
        color: document.documentElement.classList.contains("dark")
            ? "#e5e7eb"
            : "#111827",
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
};

document.addEventListener("DOMContentLoaded", () => {
    const darkModeToggle = document.getElementById("darkModeToggle");
    const htmlElement = document.documentElement;

    if (darkModeToggle) {
        const sunIcon = darkModeToggle.querySelector(".sun-icon");
        const moonIcon = darkModeToggle.querySelector(".moon-icon");

        const updateTheme = () => {
            if (htmlElement.classList.contains("dark")) {
                sunIcon.classList.add("hidden");
                moonIcon.classList.remove("hidden");
                localStorage.setItem("theme", "dark");
            } else {
                sunIcon.classList.remove("hidden");
                moonIcon.classList.add("hidden");
                localStorage.setItem("theme", "light");
            }
        };

        darkModeToggle.addEventListener("click", () => {
            htmlElement.classList.toggle("dark");
            updateTheme();
        });

        updateTheme();
    }
});
