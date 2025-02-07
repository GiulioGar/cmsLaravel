@extends('layouts.app')

@section('title', 'Accesso | Gestionale Interactive')

@section('content')
<main class="d-flex w-100">
	<div class="container d-flex flex-column">
		<div class="row vh-100">
			<div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
				<div class="d-table-cell align-middle">

					<div class="text-center mt-4">
						<h1 class="h2">Bentornato</h1>
						<p class="lead">
							Effettua il log in per procedere!
						</p>
					</div>

					<div class="card">
						<div class="card-body">
							<div class="m-sm-3">
								<!-- Visualizza eventuali errori (se presenti) -->
								@if($errors->any())
									<div class="alert alert-danger">
										<ul>
											@foreach ($errors->all() as $error)
												<li>{{ $error }}</li>
											@endforeach
										</ul>
									</div>
								@endif

								<!-- Form di login: ricordarsi di aggiornare l'azione del form -->
								<form action="{{ route('login.submit') }}" method="POST">
									@csrf
									<div class="mb-3">
										<label class="form-label">Nome utente</label>
										<input class="form-control form-control-lg" type="text" name="name" placeholder="Inserisci il tuo nome utente" required />
									</div>
									<div class="mb-3">
										<label class="form-label">Password</label>
										<div class="input-group">
											<input class="form-control form-control-lg" type="password" id="password" name="password" placeholder="Inserisci la tua password" required />
											<button type="button" class="btn btn-outline-secondary" id="togglePassword">
												👁
											</button>
										</div>
									</div>
									<div class="d-grid gap-2 mt-3">
										<button type="submit" class="btn btn-lg btn-primary">Accedi</button>
									</div>
								</form>

							</div>
						</div>
					</div>
					<div class="text-center mb-3">
						Se non hai un account contatta l'amministratore
					</div>
				</div>
			</div>
		</div>
	</div>
</main>
@endsection

@section('scripts')
<script>
document.getElementById("togglePassword").addEventListener("click", function() {
    var passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        this.textContent = "⭕"; // Cambia icona
    } else {
        passwordField.type = "password";
        this.textContent = "👀"; // Cambia icona
    }
});
</script>
@endsection
