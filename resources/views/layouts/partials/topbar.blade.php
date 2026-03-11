 <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>

                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">

<li class="nav-item dropdown">
    <a class="nav-icon dropdown-toggle"
       href="#"
       id="alertsDropdown"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false">
        <div class="position-relative">
            <i class="align-middle" data-feather="bell"></i>
            <span class="indicator" id="totalRequests">0</span>
        </div>
    </a>

    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0"
         id="alertsDropdownMenu"
         aria-labelledby="alertsDropdown">
        <div class="dropdown-menu-header">
            <span id="totalRequestsText">0</span> Nuove Richieste
        </div>
        <div class="list-group">
            <a href="#" class="list-group-item">
                <div class="row g-0 align-items-center">
                    <div class="col-2">
                        <i class="text-danger" data-feather="bell"></i>
                    </div>
                    <div class="col-10">
                        <div class="text-dark">Premio Amazon</div>
                        <div class="text-muted small mt-1">Richieste Buoni: <span id="amazonRequests">0</span></div>
                    </div>
                </div>
            </a>
            <a href="#" class="list-group-item">
                <div class="row g-0 align-items-center">
                    <div class="col-2">
                        <i class="text-warning" data-feather="bell"></i>
                    </div>
                    <div class="col-10">
                        <div class="text-dark">Premio PayPal</div>
                        <div class="text-muted small mt-1">Richieste Ricariche: <span id="paypalRequests">0</span></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="dropdown-menu-footer">
            <a href="#" class="text-muted">Mostra tutto</a>
        </div>
    </div>
</li>

            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle d-inline-block d-sm-none"
                href="#"
                id="userDropdownMobile"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                    <i class="align-middle" data-feather="settings"></i>
                </a>

                <a class="nav-link dropdown-toggle d-none d-sm-inline-block"
                href="#"
                id="userDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                    <span class="text-dark">Ciao {{ session('user_name', 'Utente') }}</span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenu" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}">Log out</a>
                    </li>
                </ul>
            </li>
                    </ul>
                </div>
            </nav>
