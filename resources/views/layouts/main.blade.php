
<!DOCTYPE html>
<html lang="it">
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

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">




<!--  SweetAlert2-->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <title>Gestionale Interactive</title>

    <!-- Carica il CSS dalla cartella public -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">



</head>
<body>
    <div class="wrapper">
        <!-- Sidebar (Navbar laterale) -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div class="main">
            <!-- Navbar in alto -->
            @include('layouts.partials.topbar')

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


 <!-- jQuery prima di DataTables -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>


<!-- DataTables + Bootstrap 5 JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest"></script>

    <!-- Carica il file JS dalla cartella public -->
    <script src="{{ asset('js/app.js') }}"></script>



        <!-- IMPORTANTISSIMO: Sezione per gli scripts aggiuntivi -->
        @yield('scripts')
</body>
</html>
