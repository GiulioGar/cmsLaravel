
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin & Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- nel layout <head> --}}


    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('img/icons/logoSmall.png') }}" />
    <link rel="canonical" href="https://demo-basic.adminkit.io/" />

    <!-- CSS di Bootstrap 5 (qui un esempio CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<!-- DataTables + Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<!-- ... -->

<!-- jQuery prima di DataTables -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- JS di Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables + Bootstrap 5 JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!--  SweetAlert2-->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <title>Gestionale Interactive</title>

    <!-- Carica il CSS dalla cartella public -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>
<body>
    <div class="wrapper">
        <!-- Sidebar (Navbar laterale) -->
        <nav id="sidebar" class="sidebar js-sidebar">
            <div class="sidebar-content js-simplebar">
                <a class="sidebar-brand" href="{{ route('index') }}">
                    <span class="align-middle"><img src="{{ asset('img/icons/logoSmall.png') }}" alt="GESTIONALE" /></span>
                </a>

                <ul class="sidebar-nav">
                    <li class="sidebar-header">Principali</li>
                    <li class="sidebar-item active">
                        <a class="sidebar-link" href="{{ route('index') }}">
                            <i class="align-middle" data-feather="home"></i> <span class="align-middle">Dashboard</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('surveys.index') }}">
                            <i class="align-middle" data-feather="folder"></i>
                            <span class="align-middle">Ricerche</span>
                        </a>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('campionamento') }}">
                            <i class="align-middle" data-feather="log-in"></i>
                            <span class="align-middle">Campionamento</span>
                        </a>
                    </li>


                    <li class="sidebar-header">Tools</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('abilita.uid') }}">
                            <i class="align-middle" data-feather="square"></i> <span class="align-middle">Abilita UID</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('autotest.index') }}">
                            <i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Autotest</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('concept.index') }}">
                            <i class="align-middle" data-feather="grid"></i> <span class="align-middle">Concept Tool</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="http://mailer.interactive-mr.com/admin/compila_mail_gest.php" target="_blank">
                            <i class="align-middle" data-feather="coffee"></i> <span class="align-middle">MAILER</span>
                        </a>
                    </li>

                    <li class="sidebar-header">Panel</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="{{ route('panel.users') }}">
                            <i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Gestione Utenti</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="">
                            <i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Premi</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="">
                            <i class="align-middle" data-feather="map"></i> <span class="align-middle">Reclutamento</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main">
            <!-- Navbar in alto -->
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>

                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">
                        <li class="nav-item dropdown">
                            <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                                <div class="position-relative">
                                    <i class="align-middle" data-feather="bell"></i>
                                    <span class="indicator" id="totalRequests">0</span>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
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
                            <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
                                <i class="align-middle" data-feather="settings"></i>
                            </a>
                            <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
                                <!-- Sostituisci la logica per il nome utente con quella che preferisci -->
                                <img style="width: 90px;" src="https://interactive-mr.com/images/logo.gif" class="avatar img-fluid rounded me-1" alt="{{ session('username') }}" />
                                <span class="text-dark">{{ session('username') }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('logout') }}">Log out</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Area per il contenuto specifico della pagina -->
            <div class="content">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row text-muted">
                        <div class="col-6 text-start">
                            <p class="mb-0">
                                <a class="text-muted" href="https://interactive-mr.com" target="_blank"><strong>Interactive-MR</strong></a>

                            </p>
                        </div>
                        <div class="col-6 text-end">
                            <ul class="list-inline">
                                <li class="list-inline-item">
                                    <a class="text-muted" href="https://adminkit.io/" target="_blank">Support</a>
                                </li>
                                <li class="list-inline-item">
                                    <a class="text-muted" href="https://adminkit.io/" target="_blank">Help Center</a>
                                </li>
                                <li class="list-inline-item">
                                    <a class="text-muted" href="https://adminkit.io/" target="_blank">Privacy</a>
                                </li>
                                <li class="list-inline-item">
                                    <a class="text-muted" href="https://adminkit.io/" target="_blank">Terms</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Carica il file JS dalla cartella public -->
    <script src="{{ asset('js/app.js') }}"></script>

        <!-- IMPORTANTISSIMO: Sezione per gli scripts aggiuntivi -->
        @yield('scripts')
</body>
</html>
