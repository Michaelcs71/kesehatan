import "./bootstrap";
import jQuery from "jquery";
window.$ = jQuery;
window.jQuery = jQuery;
import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;
import * as coreui from "@coreui/coreui";
window.coreui = coreui;
import DataTable from "datatables.net-bs5";
import "datatables.net-responsive-bs5";
window.DataTable = DataTable;
import Swal from "sweetalert2";
window.Swal = Swal;
import Chart from "chart.js/auto"; // 👈 TAMBAHIN
window.Chart = Chart; // 👈 TAMBAHIN
import { DataGrid } from "./datatable-helper";
window.DataGrid = DataGrid;

jQuery.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        "X-Requested-With": "XMLHttpRequest",
    },
});

// Mark ready - inline script can use whenKesehatanReady() to wait
window.kesehatanReady = true;
window.dispatchEvent(new Event("kesehatan:ready"));

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });
});
