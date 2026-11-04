<script>
    window.FieldControlConfig = Object.assign({
        csrfToken: @json(csrf_token()),
        prj: @json($prj),
        sid: @json($sid),
        routes: {
            closeSurvey: @json(route('close.survey')),
            reopenSurvey: @json(route('reopen.survey')),
            resetBloccate: @json(route('reset.bloccate')),
            updateRedirections: @json(route('redirections.update')),
            parseRedirections: @json(route('redirections.parse')),
            addRedirections: @json(route('redirections.add')),
            deleteRedirectionsGroup: @json(route('redirections.deleteGroup')),
            addQuotaTarget: @json(route('quotaTargets.add')),
            updateQuotaTarget: @json(route('quotaTargets.update')),
            deleteQuotaTarget: @json(route('quotaTargets.delete')),
            updateQuotaTotalInterviews: @json(route('quotaTargets.updateTotal')),
            publishQuotaConfig: @json(route('quotaTargets.publish'))
        },
        panelNamesMap: @json($panelNamesMap ?? []),
        questionMap: @json($questionMap ?? [])
    }, window.FieldControlConfig || {});
</script>

<script src="{{ asset('js/fieldControl.js') }}?v={{ @filemtime(public_path('js/fieldControl.js')) }}"></script>
