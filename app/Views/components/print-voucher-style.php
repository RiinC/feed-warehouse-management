<style>
*, *::before, *::after { box-sizing: border-box; }

html, body {
    margin: 0;
    padding: 0;
    background: #e5e7eb;
    color: #000000;
    font-family: "Times New Roman", Times, Georgia, serif;
    font-size: 11.5pt;
    line-height: 1.42;
}

.pv-toolbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 20px;
    background: #1e293b;
    color: #ffffff;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    font-size: 13.5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.pv-toolbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pv-btn {
    border: 1px solid #475569;
    background: #334155;
    color: #ffffff;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.pv-btn-primary {
    border-color: #2563eb;
    background: #2563eb;
}

.pv-sheet {
    width: 210mm;
    min-height: 297mm;
    margin: 18px auto 32px;
    padding: 14mm 15mm;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.16);
}

.print-voucher {
    font-family: "Times New Roman", Times, Georgia, serif;
    font-size: 11.5pt;
    line-height: 1.42;
    color: #000000;
    width: 100%;
}

.print-voucher table.pv-head-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.print-voucher table.pv-head-table td {
    border: none;
    padding: 0;
    vertical-align: top;
    width: 50%;
    font-size: 11pt;
}

.print-voucher .pv-title {
    text-align: center;
    margin: 10px 0 3px;
    font-size: 14pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.01em;
}

.print-voucher .pv-sub {
    text-align: center;
    font-style: italic;
    margin-bottom: 10px;
    font-size: 11pt;
}

.print-voucher .pv-meta {
    margin-bottom: 8px;
}

.print-voucher .pv-meta p {
    margin: 3px 0;
}

.print-voucher .pv-sec {
    font-weight: 700;
    margin: 11px 0 5px;
    text-transform: uppercase;
    font-size: 11pt;
}

.print-voucher table.pv-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    font-size: 10.5pt;
}

.print-voucher table.pv-table th,
.print-voucher table.pv-table td {
    border: 1px solid #000000;
    padding: 5px 6px;
    vertical-align: middle;
    color: #000000;
}

.print-voucher table.pv-table th {
    text-align: center;
    font-weight: 700;
    background: #f2f2f2;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.print-voucher .pv-num { text-align: right; }
.print-voucher .pv-center { text-align: center; }

.print-voucher table.pv-sign-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 18px;
    page-break-inside: avoid;
    break-inside: avoid;
}

.print-voucher table.pv-sign-table td {
    border: none;
    text-align: center;
    vertical-align: top;
    padding: 0 4px;
}

.print-voucher .pv-sign-title { font-weight: 700; font-size: 10.5pt; }
.print-voucher .pv-sign-note { font-style: italic; font-size: 9.5pt; margin-top: 2px; }
.print-voucher .pv-sign-space { height: 60px; }
.print-voucher .pv-sign-name { font-weight: 700; font-size: 10.5pt; }

@media print {
    @page { size: A4 portrait; margin: 12mm 14mm; }
    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .no-print, .pv-toolbar {
        display: none !important;
    }
    .pv-sheet {
        width: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }
}
</style>
