<?php helper('backoffice'); $nav = $nav ?? ''; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Backoffice') ?> — Backoffice</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --accent: #4F46E5;
        --accent-2: #06B6D4;
        --accent-soft: #EEF2FF;
        --ink: #0F172A;
        --ink-soft: #334155;
        --muted: #64748B;
        --paper: #F4F7FC;
        --card: #FFFFFF;
        --line: #E2E8F0;
        --line-soft: #EEF2F7;
        --ok-bg: #DCFCE7;
        --ok-fg: #15803D;
        --warn-bg: #FEF3C7;
        --warn-fg: #B45309;
        --danger-bg: #FEE2E2;
        --danger-fg: #B91C1C;
        --info-bg: #DBEAFE;
        --info-fg: #1D4ED8;
        --off-bg: #F1F5F9;
        --off-fg: #64748B;
        --shadow-sm: 0 1px 2px rgba(15, 23, 42, .05);
        --shadow-md: 0 4px 16px -4px rgba(15, 23, 42, .10);
        --shadow-lg: 0 20px 40px -16px rgba(15, 23, 42, .18);
        --radius: 14px;
        --radius-sm: 10px;
        --ring: 0 0 0 3px rgba(79, 70, 229, .15);
    }

    * {
        box-sizing: border-box;
    }

    html {
        -webkit-text-size-adjust: 100%;
    }

    body {
        margin: 0;
        display: flex;
        min-height: 100vh;
        background:
            radial-gradient(1200px 500px at 100% -10%, #E0E7FF 0%, transparent 60%),
            radial-gradient(900px 400px at -10% 110%, #CFFAFE 0%, transparent 55%),
            var(--paper);
        color: var(--ink);
        font-family: 'IBM Plex Sans', system-ui, -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
        line-height: 1.5;
    }

    /* ============ SIDEBAR ============ */
    .sidebar {
        width: 244px;
        flex-shrink: 0;
        background: linear-gradient(180deg, #111C3A 0%, #0B1428 100%);
        color: #fff;
        padding: 22px 14px;
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        gap: 3px;
        border-right: 1px solid rgba(255, 255, 255, .06);
        box-shadow: var(--shadow-lg);
    }

    .sidebar .brand {
        font-family: 'Fraunces', serif;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        padding: 0 12px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
    }

    .sidebar .brand::before {
        content: "";
        width: 10px;
        height: 10px;
        border-radius: 3px;
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        box-shadow: 0 0 12px rgba(79, 70, 229, .7);
    }

    .sidebar a {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        color: #A8B7DA;
        text-decoration: none;
        font-size: .9rem;
        font-weight: 500;
        transition: background .18s ease, color .18s ease, transform .18s ease;
        position: relative;
    }

    .sidebar a:hover {
        background: rgba(255, 255, 255, .07);
        color: #fff;
        transform: translateX(2px);
    }

    .sidebar a.active {
        background: linear-gradient(120deg, rgba(79, 70, 229, .9), rgba(6, 182, 212, .75));
        color: #fff;
        font-weight: 600;
        box-shadow: 0 8px 20px -8px rgba(79, 70, 229, .9);
    }

    .sidebar a.active::after {
        content: "";
        position: absolute;
        right: 10px;
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #fff;
        opacity: .9;
    }

    .sidebar .spacer {
        flex: 1;
    }

    .sidebar .who {
        padding: 12px;
        margin-bottom: 6px;
        font-size: .76rem;
        color: #7C8CB3;
        border-top: 1px solid rgba(255, 255, 255, .07);
        line-height: 1.4;
    }

    .sidebar button {
        width: 100%;
        text-align: left;
        padding: 10px 12px;
        border: 1px solid rgba(252, 165, 165, .16);
        border-radius: var(--radius-sm);
        background: rgba(252, 165, 165, .06);
        color: #FCA5A5;
        font: inherit;
        font-size: .88rem;
        font-weight: 500;
        cursor: pointer;
        transition: background .18s ease, color .18s ease;
    }

    .sidebar button:hover {
        background: rgba(239, 68, 68, .16);
        color: #FECACA;
    }

    /* ============ MAIN ============ */
    .main {
        flex: 1;
        min-width: 0;
        padding: 34px 38px 48px;
        animation: fadeUp .45s ease both;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 26px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--line);
    }

    h1 {
        margin: 0;
        font-family: 'Fraunces', serif;
        font-size: 1.7rem;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: var(--ink);
    }

    /* ============ PANEL ============ */
    .panel {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 22px 24px;
        box-shadow: var(--shadow-sm);
        transition: box-shadow .22s ease, border-color .22s ease;
    }

    .panel:hover {
        box-shadow: var(--shadow-md);
        border-color: #CFDBEC;
    }

    .panel-title {
        margin: 0 0 14px;
        font-size: .98rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .panel-title::before {
        content: "";
        width: 4px;
        height: 16px;
        border-radius: 4px;
        background: linear-gradient(180deg, var(--accent), var(--accent-2));
    }

    /* ============ BUTTONS ============ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 18px;
        border: 1px solid transparent;
        border-radius: var(--radius-sm);
        font: inherit;
        font-weight: 600;
        font-size: .87rem;
        cursor: pointer;
        text-decoration: none;
        transition: transform .15s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
        white-space: nowrap;
    }

    .btn:active {
        transform: translateY(1px) scale(.99);
    }

    .btn-primary {
        background: linear-gradient(120deg, var(--accent), var(--accent-2));
        color: #fff;
        box-shadow: 0 8px 18px -8px rgba(79, 70, 229, .8);
    }

    .btn-primary:hover {
        box-shadow: 0 12px 24px -10px rgba(79, 70, 229, .95);
        filter: brightness(1.04);
    }

    .btn-ghost {
        background: #fff;
        border-color: var(--line);
        color: var(--ink-soft);
        box-shadow: var(--shadow-sm);
    }

    .btn-ghost:hover {
        border-color: var(--accent);
        color: var(--accent);
        background: var(--accent-soft);
    }

    .btn-sm {
        padding: 7px 13px;
        font-size: .8rem;
    }

    .btn-danger {
        color: var(--danger-fg);
    }

    .btn-danger:hover {
        border-color: var(--danger-fg);
        color: var(--danger-fg);
        background: var(--danger-bg);
    }

    .btn[disabled] {
        opacity: .45;
        cursor: not-allowed;
        transform: none;
    }

    /* ============ ALERTS ============ */
    .alert {
        margin-bottom: 18px;
        padding: 13px 16px;
        border-radius: var(--radius-sm);
        font-size: .88rem;
        border: 1px solid transparent;
        animation: fadeUp .35s ease both;
    }

    .alert-error {
        background: var(--danger-bg);
        color: var(--danger-fg);
        border-color: #FECACA;
    }

    .alert-success {
        background: var(--ok-bg);
        color: var(--ok-fg);
        border-color: #BBF7D0;
    }

    .alert ul {
        margin: 0;
        padding-left: 18px;
    }

    /* ============ TABLE ============ */
    .table-wrap {
        overflow-x: auto;
        border-radius: var(--radius-sm);
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: .9rem;
    }

    th,
    td {
        padding: 13px 12px;
        text-align: left;
        border-bottom: 1px solid var(--line-soft);
        vertical-align: middle;
    }

    th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        font-weight: 600;
        background: #F8FAFC;
        position: sticky;
        top: 0;
    }

    th:first-child {
        border-top-left-radius: var(--radius-sm);
    }

    th:last-child {
        border-top-right-radius: var(--radius-sm);
    }

    tbody tr {
        transition: background .15s ease;
    }

    tbody tr:hover {
        background: #F8FAFF;
    }

    tr:last-child td {
        border-bottom: 0;
    }

    /* ============ TEXT / BADGE ============ */
    .muted {
        color: var(--muted);
        font-size: .82rem;
    }

    .badge {
        display: inline-block;
        padding: 4px 11px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .01em;
        white-space: nowrap;
    }

    .badge-ok {
        background: var(--ok-bg);
        color: var(--ok-fg);
    }

    .badge-off {
        background: var(--off-bg);
        color: var(--off-fg);
    }

    .badge-warn {
        background: var(--warn-bg);
        color: var(--warn-fg);
    }

    .badge-danger {
        background: var(--danger-bg);
        color: var(--danger-fg);
    }

    .badge-info {
        background: var(--info-bg);
        color: var(--info-fg);
    }

    /* ============ FORMS ============ */
    .filters {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 18px;
        padding: 16px;
        background: #F8FAFF;
        border: 1px solid var(--line);
        border-radius: var(--radius);
    }

    label {
        display: block;
        margin-bottom: 7px;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: var(--muted);
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 10px 13px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: #fff;
        font: inherit;
        font-size: .92rem;
        color: var(--ink);
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    input,
    select {
        height: 42px;
    }

    textarea {
        resize: vertical;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: var(--ring);
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 22px;
    }

    .form-grid .full {
        grid-column: 1 / -1;
    }

    .hint {
        margin-top: 5px;
        font-size: .76rem;
        color: var(--muted);
    }

    .actions {
        display: flex;
        gap: 10px;
        margin-top: 24px;
    }

    .inline {
        display: inline;
    }

    .pager {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        align-items: center;
        margin-top: 18px;
        font-size: .84rem;
        color: var(--muted);
    }

    /* ============ DASHBOARD ============ */
    .quick {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    /* ============ LIST ============ */
    .list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .list li {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid var(--line-soft);
        font-size: .9rem;
    }

    .list li:last-child {
        border-bottom: 0;
    }

    .list-time {
        flex: 0 0 66px;
        font-size: .78rem;
        font-weight: 700;
        color: var(--accent);
        background: var(--accent-soft);
        padding: 4px 8px;
        border-radius: 8px;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    .list-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .list-main a {
        color: var(--accent);
        font-weight: 600;
        text-decoration: none;
        transition: color .15s ease;
    }

    .list-main a:hover {
        color: var(--accent-2);
        text-decoration: underline;
    }

    /* ============ FILTER SIZING ============ */
    .filters>div {
        flex: 1 1 150px;
        min-width: 140px;
    }

    .filters>.f-wide {
        flex: 2 1 240px;
    }

    .filters>.f-actions {
        flex: 0 0 auto;
        display: flex;
        gap: 8px;
        align-items: flex-end;
        padding-bottom: 1px;
    }

    /* ============ MISC ============ */
    .inv {
        font-weight: 600;
        color: var(--accent);
        text-decoration: none;
    }

    .row-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .dl {
        display: grid;
        grid-template-columns: 110px 1fr;
        gap: 9px 14px;
        margin: 0;
        font-size: .9rem;
    }

    .dl dt {
        color: var(--muted);
    }

    .dl dd {
        margin: 0;
        word-break: break-word;
    }

    .dl-money dd {
        text-align: right;
    }

    .dl .strong {
        font-weight: 700;
        color: var(--ink);
    }

    .text-danger {
        color: var(--danger-fg);
    }

    .tabs {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--line);
    }

    .tab {
        padding: 10px 15px;
        border: 0;
        border-bottom: 2px solid transparent;
        background: transparent;
        color: var(--muted);
        font: inherit;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        transition: color .15s ease, border-color .15s ease;
    }

    .tab:hover {
        color: var(--ink);
    }

    .tab.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    .tab-n {
        display: inline-block;
        min-width: 20px;
        padding: 1px 6px;
        border-radius: 999px;
        background: var(--accent-soft);
        color: var(--accent);
        font-size: .72rem;
        text-align: center;
    }

    .sub-title {
        margin: 22px 0 9px;
        font-size: .9rem;
    }

    .action-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .form-box {
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        padding: 12px;
        background: #fff;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .form-box[open] {
        flex: 1 1 100%;
        box-shadow: var(--shadow-md);
        border-color: #CFDBEC;
    }

    .form-box summary {
        list-style: none;
        cursor: pointer;
        font-weight: 600;
        font-size: .88rem;
    }

    .form-box summary::-webkit-details-marker {
        display: none;
    }

    .form-box form {
        margin-top: 14px;
    }

    .handover-card {
        margin-bottom: 18px;
        padding: 16px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: #FCFDFF;
    }

    .handover-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .timeline {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .timeline li {
        display: flex;
        gap: 16px;
        padding: 11px 0;
        border-bottom: 1px solid var(--line-soft);
        font-size: .9rem;
    }

    .tl-time {
        flex: 0 0 120px;
        color: var(--muted);
        font-size: .8rem;
        font-variant-numeric: tabular-nums;
    }

    .notes-box {
        padding: 14px;
        border-radius: var(--radius-sm);
        background: var(--paper);
        border: 1px solid var(--line-soft);
        font-size: .88rem;
        white-space: normal;
    }

    /* ===== DASHBOARD: STATS BAR ===== */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1px;
        background: var(--line);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }

    .stat {
        background: var(--card);
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .stat-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .stat-value {
        font-family: 'Fraunces', serif;
        font-size: 2.1rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: -0.02em;
        color: var(--ink);
        font-variant-numeric: tabular-nums;
    }

    .stat-value small {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: .82rem;
        font-weight: 500;
        color: var(--muted);
        margin-left: 2px;
    }

    .stat-sub {
        font-size: .76rem;
        color: var(--muted);
    }

    .stat.highlight {
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
    }

    .stat.highlight .stat-label {
        color: rgba(255, 255, 255, .75);
    }

    .stat.highlight .stat-value {
        color: #fff;
    }

    .stat.highlight .stat-sub {
        color: rgba(255, 255, 255, .85);
    }

    .util-track {
        margin-top: 6px;
        height: 4px;
        border-radius: 999px;
        background: var(--line);
        overflow: hidden;
    }

    .util-track span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, var(--accent), var(--accent-2));
        border-radius: 999px;
        transition: width .6s ease;
    }

    .stat.highlight .util-track {
        background: rgba(255, 255, 255, .22);
    }

    .stat.highlight .util-track span {
        background: #fff;
    }

    /* ===== DASHBOARD: FINANCE STRIP ===== */
    .finance-strip {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px;
        background: var(--line);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }

    .fin-cell {
        background: var(--card);
        padding: 18px 22px;
    }

    .fin-cell .stat-label {
        margin-bottom: 8px;
    }

    .fin-cell .fin-amount {
        font-family: 'Fraunces', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--ink);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.01em;
        line-height: 1.1;
    }

    .fin-cell .fin-amount.is-danger {
        color: var(--danger-fg);
    }

    .fin-cell .fin-note {
        font-size: .76rem;
        color: var(--muted);
        margin-top: 4px;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        padding: 26px 12px;
        text-align: center;
    }

    .empty-state p {
        margin: 0;
        color: var(--muted);
        font-size: .84rem;
        font-style: italic;
    }

    /* ===== STAT PILL ===== */
    .stat-strip {
        margin-bottom: 14px;
    }

    .stat-pill {
        display: inline-flex;
        align-items: baseline;
        gap: 8px;
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        font-size: .8rem;
        background: var(--warn-bg);
        color: var(--warn-fg);
        font-weight: 500;
    }

    .stat-pill .num {
        font-family: 'Fraunces', serif;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .badge-group {
        display: inline-flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    /* ===== ROLE STRIP ===== */
    .role-strip {
        margin-top: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 14px 20px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
    }

    .role-strip .role-label {
        font-size: .82rem;
        color: var(--muted);
    }

    .role-strip .role-label strong {
        font-family: 'Fraunces', serif;
        font-size: 1rem;
        color: var(--ink);
        margin-left: 6px;
        font-weight: 600;
    }

    .role-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    /* ============ SCROLLBAR ============ */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 999px;
        border: 2px solid var(--paper);
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94A3B8;
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 1080px) {
        .stats-bar {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-2 {
            grid-template-columns: 1fr;
        }

        .detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 860px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
            position: static;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            padding: 12px;
            gap: 6px;
            border-right: 0;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
        }

        .sidebar .brand {
            padding: 0 12px 0 4px;
        }

        .sidebar .spacer,
        .sidebar .who {
            display: none;
        }

        .sidebar a {
            padding: 8px 12px;
        }

        .sidebar a.active::after {
            display: none;
        }

        .sidebar form {
            display: inline;
        }

        .sidebar button {
            width: auto;
            padding: 8px 12px;
        }

        .main {
            padding: 22px 16px 40px;
        }

        .page-head {
            align-items: flex-start;
        }

        h1 {
            font-size: 1.4rem;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .table-wrap {
            overflow-x: auto;
        }
    }

    @media (max-width: 560px) {
        .stats-bar {
            grid-template-columns: 1fr;
        }

        .finance-strip {
            grid-template-columns: 1fr;
        }

        .stat-value {
            font-size: 1.85rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation: none !important;
            transition: none !important;
        }
    }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand">Backoffice</div>
        <a href="<?= site_url('backoffice/dashboard') ?>"
            class="<?= $nav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <?php if (admin_can('product.view') || admin_can('product.manage')): ?>
        <?php if (admin_can('booking.view')): ?>
        <a href="<?= site_url('backoffice/bookings') ?>" class="<?= $nav === 'booking' ? 'active' : '' ?>">Booking</a>
        <?php endif; ?>
        <a href="<?= site_url('backoffice/produk') ?>" class="<?= $nav === 'produk' ? 'active' : '' ?>">Produk</a>
        <?php endif; ?>
        <?php if (admin_can('jenis.manage')): ?>
        <a href="<?= site_url('backoffice/jenis') ?>" class="<?= $nav === 'jenis' ? 'active' : '' ?>">Jenis</a>
        <?php endif; ?>
        <?php if (admin_can('customer.verify')): ?>
        <a href="<?= site_url('backoffice/verifikasi-identitas') ?>"
            class="<?= $nav === 'verifikasi' ? 'active' : '' ?>">Verifikasi Identitas</a>
        <?php endif; ?>
        <div class="spacer"></div>
        <div class="who"><?= esc(session()->get('admin_name')) ?> · <?= esc(session()->get('admin_role')) ?></div>
        <form action="<?= site_url('backoffice/logout') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit">Keluar</button>
        </form>
    </aside>

    <main class="main">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
                <li><?= esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>
</body>

</html>