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
