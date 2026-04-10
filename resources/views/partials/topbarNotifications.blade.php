<li class="nav-item dropdown topbar-alerts">
    <a class="nav-icon dropdown-toggle topbar-alerts-toggle"
       href="#"
       id="alertsDropdown"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false">
        <div class="position-relative">
            <i class="align-middle" data-feather="bell"></i>
            <span class="indicator topbar-alerts-indicator d-none" id="totalRequests">0</span>
        </div>
    </a>

    <div class="dropdown-menu dropdown-menu-end py-0 topbar-alerts-menu"
         id="alertsDropdownMenu"
         aria-labelledby="alertsDropdown">
        <div class="topbar-alerts-header">
            <div class="topbar-alerts-header-main">
                <div>
                    <div class="topbar-alerts-title">Attività da gestire</div>
                    <div class="topbar-alerts-subtitle">Monitoraggio operativo rapido</div>
                </div>

                <div class="topbar-alerts-total-pill">
                    <span class="topbar-alerts-total-pill-label">Totale</span>
                    <span class="topbar-alerts-total-pill-value" id="totalRequestsText">0</span>
                </div>
            </div>
        </div>

        <div class="topbar-alerts-body">
            <a href="{{ route('premi.panel', ['type' => 'amazon', 'status' => 0]) }}"
               class="topbar-alert-item topbar-alert-item-amazon">
                <div class="topbar-alert-icon">
                    <i data-feather="gift"></i>
                </div>
                <div class="topbar-alert-content">
                    <div class="topbar-alert-label">Premi Amazon</div>
                    <div class="topbar-alert-description">Buoni da erogare</div>
                </div>
                <div class="topbar-alert-count" id="amazonRequests">0</div>
            </a>

            <a href="{{ route('premi.panel', ['type' => 'paypal', 'status' => 0]) }}"
               class="topbar-alert-item topbar-alert-item-paypal">
                <div class="topbar-alert-icon">
                    <i data-feather="credit-card"></i>
                </div>
                <div class="topbar-alert-content">
                    <div class="topbar-alert-label">Premi PayPal</div>
                    <div class="topbar-alert-description">Pagamenti da eseguire</div>
                </div>
                <div class="topbar-alert-count" id="paypalRequests">0</div>
            </a>

            <a href="{{ route('tickets.index') }}"
               class="topbar-alert-item topbar-alert-item-ticket">
                <div class="topbar-alert-icon">
                    <i data-feather="message-square"></i>
                </div>
                <div class="topbar-alert-content">
                    <div class="topbar-alert-label">Ticket aperti</div>
                    <div class="topbar-alert-description">Richieste utenti da gestire</div>
                </div>
                <div class="topbar-alert-count" id="openTickets">0</div>
            </a>
        </div>

<div class="topbar-alerts-footer">
    <a href="{{ route('surveys.index') }}" class="topbar-alerts-footer-link">
        <span class="topbar-alerts-footer-left">
            <span class="topbar-alerts-footer-icon-wrap">
                <i data-feather="activity" class="topbar-alerts-footer-icon"></i>
            </span>
            <span class="topbar-alerts-footer-text">
                Ricerche in corso
            </span>
        </span>

        <span class="topbar-alerts-footer-right">
            <span class="topbar-alerts-footer-badge" id="runningSurveys">0</span>
            <i data-feather="chevron-right" class="topbar-alerts-footer-arrow"></i>
        </span>
    </a>
</div>
    </div>
</li>
