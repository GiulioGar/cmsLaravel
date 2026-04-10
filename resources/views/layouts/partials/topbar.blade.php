 <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>

                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">

@include('partials.topbarNotifications')

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
