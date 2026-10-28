<script>
    window.FieldControlConfig = Object.assign({
        csrfToken: @json(csrf_token()),
        prj: @json($prj),
        sid: @json($sid),
        routes: {
            closeSurvey: @json(route('close.survey')),
            reopenSurvey: @json(route('reopen.survey')),
            resetBloccate: @json(route('reset.bloccate'))
        }
    }, window.FieldControlConfig || {});
</script>

<script src="{{ asset('js/fieldControl.js') }}?v={{ @filemtime(public_path('js/fieldControl.js')) }}"></script>
