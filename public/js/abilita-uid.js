(function () {
    const config = window.AbilitaUidConfig || {};
    const surveyData = Array.isArray(config.surveys) ? config.surveys : [];
    const csrfToken = config.csrfToken || '';
    const urls = config.urls || {};

    function getSurveyBySid(selectedSid) {
        return surveyData.find(item => item.sid === selectedSid) || null;
    }

    // === AGGIORNA PRJ E LINK GUEST (SINISTRA) ===
    function updatePrjAndGuestLink(selectedSid) {
        const s = getSurveyBySid(selectedSid);
        const prjField = document.getElementById('prj');
        const guestInput = document.getElementById('guestLink');

        if (prjField) {
            prjField.value = s ? s.prj_name : '';
        }

        if (guestInput) {
            if (selectedSid && s) {
                guestInput.value = 'https://www.primisoft.com/primis/run.do?sid=' + selectedSid + '&prj=' + s.prj_name + '&uid=GUEST';
            } else {
                guestInput.value = '';
            }
        }
    }

    // === COPIA LINK GUEST ===
    function copyGuestLink() {
        const guestInput = document.getElementById('guestLink');
        if (!guestInput || !guestInput.value) {
            Swal.fire({ icon: 'info', title: 'Nessun link GUEST disponibile' });
            return;
        }

        guestInput.select();
        guestInput.setSelectionRange(0, 99999);
        document.execCommand('copy');

        Swal.fire({
            icon: 'success',
            title: 'Copiato!',
            text: 'Il link GUEST è stato copiato negli appunti.',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // === COPIA LINK GENERATI ===
    function copyLinks() {
        const textarea = document.getElementById('generatedLinks');
        if (!textarea) {
            Swal.fire({ icon: 'info', title: 'Nessun link da copiare' });
            return;
        }

        textarea.select();
        textarea.setSelectionRange(0, 99999);
        document.execCommand('copy');

        Swal.fire({
            icon: 'success',
            title: 'Copiati!',
            text: 'Tutti i link sono stati copiati negli appunti.',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // === ESPORTA CSV ===
    function exportCSV() {
        const textarea = document.getElementById('generatedLinks');
        if (!textarea) {
            Swal.fire({ icon: 'info', title: 'Nessun link da esportare' });
            return;
        }

        const links = textarea.value.trim().split('\n').filter(function (l) {
            return l !== '';
        });

        if (links.length === 0) {
            Swal.fire({ icon: 'info', title: 'Nessun link da esportare' });
            return;
        }

        const sid = new URLSearchParams(new URL(links[0]).search).get('sid') || 'SID';
        const pan = new URLSearchParams(new URL(links[0]).search).get('pan') || 'PANEL';
        const filename = 'links_' + pan + '_' + sid + '.csv';

        let csvContent = 'Url;Code\n';

        links.forEach(function (url) {
            const code = new URLSearchParams(new URL(url).search).get('uid') || '';
            csvContent += url + ';' + code + '\n';
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }

    // === AJAX INSERIMENTO PANEL ===
    function bindAddPanelForm() {
        const form = document.getElementById('formAddPanel');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch(urls.storePanel, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(Object.fromEntries(new FormData(form)))
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Panel aggiunto!',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        setTimeout(function () {
                            location.reload();
                        }, 1600);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Errore durante il salvataggio' });
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Errore di rete' });
                });
        });
    }

    // === ELIMINA PANEL ===
    function deletePanel(id) {
        Swal.fire({
            title: 'Sei sicuro?',
            text: 'Questa azione eliminerà il panel!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sì, elimina',
            cancelButtonText: 'Annulla'
        }).then(function (result) {
            if (result.isConfirmed) {
                fetch(urls.deletePanelBase + '/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            const row = document.getElementById('panelRow' + id);
                            if (row) {
                                row.remove();
                            }

                            Swal.fire('Eliminato!', 'Il panel è stato rimosso.', 'success');
                        }
                    })
                    .catch(function () {
                        Swal.fire({ icon: 'error', title: 'Errore di rete' });
                    });
            }
        });
    }

    // === COLONNA DESTRA: AGGIORNA PRJ ===
    function updatePrjRight(selectedSid) {
        const s = getSurveyBySid(selectedSid);

        const prjInput = document.getElementById('prjRight');
        const uidIidCard = document.getElementById('uidIidCard');
        const resultsCard = document.getElementById('resultsCard');
        const resultsHint = document.getElementById('resultsHint');
        const resultsLoading = document.getElementById('resultsLoading');
        const resultsContent = document.getElementById('resultsContent');

        if (prjInput) {
            prjInput.value = s ? s.prj_name : '';
        }

        const hasSid = !!selectedSid;

        if (uidIidCard) {
            uidIidCard.style.display = hasSid ? '' : 'none';
        }

        if (resultsCard) {
            resultsCard.style.display = hasSid ? '' : 'none';
        }

        if (!hasSid) {
            if (resultsHint) resultsHint.style.display = 'flex';
            if (resultsLoading) resultsLoading.style.display = 'none';
            if (resultsContent) resultsContent.style.display = 'none';

            const totalFiles = document.getElementById('totalFiles');
            const lastFile = document.getElementById('lastFile');
            const statusTable = document.getElementById('statusTable');
            const lastActions = document.getElementById('lastActions');
            const uidInput = document.getElementById('uidInput');
            const detailTable = document.getElementById('detailTable');
            const searchInput = document.getElementById('searchUidIid');

            if (totalFiles) totalFiles.innerText = '0';
            if (lastFile) lastFile.innerText = '—';

            if (statusTable) {
                statusTable.innerHTML =
                    '<tr>' +
                        '<td class="text-start">' +
                            '<span class="au-pill au-pill--muted">' +
                                '<i class="fa-solid fa-minus"></i> —' +
                            '</span>' +
                        '</td>' +
                        '<td class="text-end text-muted">—</td>' +
                    '</tr>';
            }

            if (detailTable) {
                detailTable.innerHTML =
                    '<tr>' +
                        '<td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>' +
                    '</tr>';
            }

            if (lastActions) {
                lastActions.innerHTML = '<li class="list-group-item text-muted">Nessuna operazione recente</li>';
            }

            if (uidInput) {
                uidInput.value = '';
            }

            updateResetButtonState();

            if (searchInput) {
                searchInput.value = '';
            }

            resetSearchResultsTable('Nessuna ricerca eseguita');
        }
    }

    function parseUidIidInputLines() {
        const input = document.getElementById('uidInput');
        if (!input) return [];

        return input.value
            .split(/\r\n|\r|\n/)
            .map(function (v) {
                return v.trim();
            })
            .filter(function (v) {
                return v !== '';
            });
    }

    function isNumericOnlyLines(lines) {
        if (!lines.length) return false;

        return lines.every(function (v) {
            return /^\d+$/.test(v);
        });
    }

    function updateResetButtonState() {
        const btnReset = document.getElementById('btn-reset-iids');
        const lines = parseUidIidInputLines();

        if (!btnReset) return;

        btnReset.disabled = !isNumericOnlyLines(lines);
    }

    // ====== UI HELPERS ======
    function setResultsLoading(isLoading) {
        const hint = document.getElementById('resultsHint');
        const loading = document.getElementById('resultsLoading');
        const content = document.getElementById('resultsContent');

        if (isLoading) {
            if (hint) hint.style.display = 'none';
            if (content) content.style.display = 'none';
            if (loading) loading.style.display = 'flex';
        } else {
            if (loading) loading.style.display = 'none';
        }
    }

    function setButtonLoading(buttonId, isLoading, loadingHtml) {
        const btn = document.getElementById(buttonId);
        if (!btn) return;

        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = loadingHtml || '<span class="spinner-border spinner-border-sm me-2"></span>Caricamento...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    }

    function resetSearchResultsTable(message) {
        const tbody = document.getElementById('searchResultsTable');
        if (!tbody) return;

        tbody.innerHTML =
            '<tr>' +
                '<td colspan="4" class="text-center text-muted">' + message + '</td>' +
            '</tr>';
    }

    // === AGGIORNA FINESTRA RISULTATI ===
    function refreshResults() {
        const sid = document.getElementById('sidRight').value;
        const prj = document.getElementById('prjRight').value;

        if (!sid || !prj) {
            Swal.fire({ icon: 'warning', title: 'Attenzione', text: 'Seleziona prima SID e PRJ.' });
            return;
        }

        setButtonLoading('btn-refresh-results', true, '<span class="spinner-border spinner-border-sm me-2"></span>Aggiornamento...');
        setResultsLoading(true);

        fetch(urls.showData, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ sid: sid, prj: prj })
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                setResultsLoading(false);
                setButtonLoading('btn-refresh-results', false);

                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
                    return;
                }

                const resultsContent = document.getElementById('resultsContent');
                const totalFiles = document.getElementById('totalFiles');
                const lastFile = document.getElementById('lastFile');

                if (resultsContent) resultsContent.style.display = 'block';
                if (totalFiles) totalFiles.innerText = data.totalFiles;
                if (lastFile) lastFile.innerText = data.lastFile;

                const statusTable = document.getElementById('statusTable');
                if (statusTable) {
                    statusTable.innerHTML = '';

                    const STATUS_META = [
                        { code: 0, label: 'Sospesa', icon: 'fa-pause-circle', cls: 'au-pill--0' },
                        { code: 3, label: 'Completata', icon: 'fa-check-circle', cls: 'au-pill--3' },
                        { code: 4, label: 'Screenout', icon: 'fa-circle-exclamation', cls: 'au-pill--4' },
                        { code: 5, label: 'Quota full', icon: 'fa-ban', cls: 'au-pill--5' },
                        { code: 6, label: 'Guest', icon: 'fa-user', cls: 'au-pill--6' },
                        { code: 7, label: 'Bloccata', icon: 'fa-lock', cls: 'au-pill--7' }
                    ];

                    STATUS_META.forEach(function (s) {
                        const count = (data.statusCounts && data.statusCounts[s.code]) ? data.statusCounts[s.code] : 0;

                        statusTable.innerHTML +=
                            '<tr>' +
                                '<td class="text-start">' +
                                    '<span class="au-pill ' + s.cls + '">' +
                                        '<i class="fa-solid ' + s.icon + '"></i> ' +
                                        s.code + ' · ' + s.label +
                                    '</span>' +
                                '</td>' +
                                '<td class="text-end fw-semibold">' + count + '</td>' +
                            '</tr>';
                    });
                }

                const detailTable = document.getElementById('detailTable');
                if (detailTable) {
                    detailTable.innerHTML = '';

                    const rows = Array.isArray(data.detailRows) ? data.detailRows : [];

                    if (!rows.length) {
                        detailTable.innerHTML =
                            '<tr>' +
                                '<td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>' +
                            '</tr>';
                    } else {
                        rows.forEach(function (row) {
                            detailTable.innerHTML +=
                                '<tr>' +
                                    '<td class="text-start">' + (row.iid ?? '—') + '</td>' +
                                    '<td class="text-start">' + (row.uid ?? '—') + '</td>' +
                                    '<td class="text-start">' + (row.status ?? '—') + '</td>' +
                                '</tr>';
                        });
                    }
                }
            })
            .catch(function () {
                setResultsLoading(false);
                setButtonLoading('btn-refresh-results', false);
                Swal.fire({ icon: 'error', title: 'Errore di rete' });
            });
    }

    function searchUidIidRecord() {
        const sid = document.getElementById('sidRight').value;
        const term = document.getElementById('searchUidIid').value.trim();

        if (!sid) {
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Seleziona prima un SID.'
            });
            return;
        }

        if (!term) {
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Inserisci un UID o un IID da cercare.'
            });
            return;
        }

        setButtonLoading('btn-search-record', true, '<span class="spinner-border spinner-border-sm me-2"></span>Ricerca...');

        fetch(urls.searchRecords, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ sid: sid, term: term })
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                setButtonLoading('btn-search-record', false);

                const tbody = document.getElementById('searchResultsTable');
                if (!tbody) return;

                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Errore', text: data.message || 'Errore durante la ricerca.' });
                    resetSearchResultsTable('Errore durante la ricerca');
                    return;
                }

                const rows = Array.isArray(data.rows) ? data.rows : [];

                if (!rows.length) {
                    resetSearchResultsTable('Nessun record trovato');
                    return;
                }

                tbody.innerHTML = '';

                rows.forEach(function (row) {
                    tbody.innerHTML +=
                        '<tr>' +
                            '<td class="text-start">' + (row.iid ?? '—') + '</td>' +
                            '<td class="text-start">' + (row.uid ?? '—') + '</td>' +
                            '<td class="text-start">' + (row.status ?? '—') + '</td>' +
                            '<td class="text-start">' + (row.prj_name ?? '—') + '</td>' +
                        '</tr>';
                });
            })
            .catch(function () {
                setButtonLoading('btn-search-record', false);
                resetSearchResultsTable('Errore di rete');
                Swal.fire({ icon: 'error', title: 'Errore di rete' });
            });
    }

    // === ABILITA UID ===
    async function enableUids() {
    const sid = document.getElementById('sidRight').value;
    const prj = document.getElementById('prjRight').value;
    const rawValue = document.getElementById('uidInput').value.trim();

    if (!sid || !prj || !rawValue) {
        Swal.fire({
            icon: 'warning',
            title: 'Attenzione',
            text: 'Seleziona SID/PRJ e inserisci almeno un UID.'
        });
        return;
    }

    let uids = rawValue
        .split(/\r\n|\r|\n/)
        .map(function (v) { return v.trim(); })
        .filter(function (v) { return v !== ''; });

    uids = Array.from(new Set(uids));

    if (!uids.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Attenzione',
            text: 'Nessun UID valido trovato.'
        });
        return;
    }

    const chunks = splitArrayIntoChunks(uids, 1000);

    setButtonLoading(
        'btn-enable-uids',
        true,
        '<span class="spinner-border spinner-border-sm me-2"></span>Abilitazione...'
    );

    let totalInserted = 0;
    let lastActions = [];

    try {
        for (let i = 0; i < chunks.length; i++) {
            const response = await fetch(urls.enableUids, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    sid: sid,
                    prj: prj,
                    uids: chunks[i].join('\n')
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Errore durante l’abilitazione UID.');
            }

            totalInserted += Number(data.count || 0);

            if (Array.isArray(data.actions) && data.actions.length) {
                lastActions = data.actions;
            }

            const btn = document.getElementById('btn-enable-uids');
            if (btn) {
            const processed = Math.min((i + 1) * 1000, uids.length);
            const percent = Math.round((processed / uids.length) * 100);

            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Abilitazione... ' + percent + '% (' + processed + '/' + uids.length + ')';
            }
        }

        updateLog(lastActions);
        refreshResults();

        Swal.fire({
            icon: 'success',
            title: 'UID abilitati',
            text: totalInserted + ' UID inseriti.'
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Errore',
            text: error.message || 'Errore di rete'
        });
    } finally {
        setButtonLoading('btn-enable-uids', false);
    }
}

    function splitArrayIntoChunks(items, chunkSize) {
    const chunks = [];

    for (let i = 0; i < items.length; i += chunkSize) {
        chunks.push(items.slice(i, i + chunkSize));
    }

    return chunks;
}

    // === RESET IID ===
    function resetIids() {
        const sid = document.getElementById('sidRight').value;
        const prj = document.getElementById('prjRight').value;
        const iids = document.getElementById('uidInput').value.trim();

        if (!sid || !prj || !iids) {
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Seleziona SID/PRJ e inserisci almeno un IID.'
            });
            return;
        }

        setButtonLoading('btn-reset-iids', true, '<span class="spinner-border spinner-border-sm me-2"></span>Verifica file...');

        fetch(urls.previewResetIids, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ sid: sid, prj: prj, iids: iids })
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                setButtonLoading('btn-reset-iids', false);

                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
                    return;
                }

                const files = Array.isArray(data.files) ? data.files : [];
                const maxPreview = 12;

                let filesHtml = '';

                if (files.length > 0) {
                    const previewList = files.slice(0, maxPreview)
                        .map(function (f) {
                            return '<li style="text-align:left;">' + f + '</li>';
                        })
                        .join('');

                    const extra = files.length > maxPreview
                        ? '<div class="text-muted mt-2">...e altri ' + (files.length - maxPreview) + ' file</div>'
                        : '';

                    filesHtml =
                        '<div class="mt-3 text-start">' +
                            '<div><strong>Stai per eliminare questi file:</strong></div>' +
                            '<ul class="mt-2 mb-1" style="max-height:220px; overflow:auto; padding-left:18px;">' +
                                previewList +
                            '</ul>' +
                            extra +
                        '</div>';
                } else {
                    filesHtml =
                        '<div class="mt-3 text-start text-muted">' +
                            'Nessun file .sre trovato per gli IID inseriti. Verrà eseguito solo il reset nel database.' +
                        '</div>';
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Conferma eliminazione',
                    html:
                        '<div>' +
                            '<div>Stai per resettare gli IID selezionati.</div>' +
                            filesHtml +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Sì, procedi',
                    cancelButtonText: 'Annulla',
                    width: 700
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    setButtonLoading('btn-reset-iids', true, '<span class="spinner-border spinner-border-sm me-2"></span>Reset...');

                    fetch(urls.resetIids, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ sid: sid, prj: prj, iids: iids })
                    })
                        .then(function (res) {
                            return res.json();
                        })
                        .then(function (data) {
                            setButtonLoading('btn-reset-iids', false);

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Reset completato',
                                    html: 'Aggiornati <b>' + data.updated + '</b> record<br>Cancellati <b>' + data.deleted + '</b> file'
                                });
                                updateLog(data.actions);
                                refreshResults();
                            } else {
                                Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
                            }
                        })
                        .catch(function () {
                            setButtonLoading('btn-reset-iids', false);
                            Swal.fire({ icon: 'error', title: 'Errore di rete' });
                        });
                });
            })
            .catch(function () {
                setButtonLoading('btn-reset-iids', false);
                Swal.fire({ icon: 'error', title: 'Errore di rete' });
            });
    }

    // === AGGIORNA LOG DINAMICO ===
    function updateLog(actions) {
        const list = document.getElementById('lastActions');
        if (!list) return;

        if (!actions || actions.length === 0) {
            list.innerHTML = '<li class="list-group-item text-muted">Nessuna operazione recente</li>';
            return;
        }

        list.innerHTML = '';

        actions.slice().reverse().forEach(function (a) {
            list.innerHTML += '<li class="list-group-item list-group-item-light">' + a + '</li>';
        });
    }

    function bindGenerateFormLoading() {
        const form = document.querySelector('form[action="' + urls.generate + '"]');
        const btn = document.getElementById('btn-genera-links');

        if (!form || !btn) return;

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generazione...';
        });
    }

    function bindSearchEnter() {
        const searchInput = document.getElementById('searchUidIid');
        if (!searchInput) return;

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUidIidRecord();
            }
        });
    }

    function bindUidInputWatcher() {
        const uidInput = document.getElementById('uidInput');
        if (!uidInput) return;

        uidInput.addEventListener('input', updateResetButtonState);
        updateResetButtonState();
    }

    function initRightPanelState() {
        const sidRight = document.getElementById('sidRight');
        if (sidRight) {
            updatePrjRight(sidRight.value);
        }
    }

    function bindLeftSideActions() {
    const sidSelect = document.getElementById('sid');
    const copyGuestBtn = document.getElementById('btn-copy-guest-link');
    const copyLinksBtn = document.getElementById('btn-copy-links');
    const exportCsvBtn = document.getElementById('btn-export-csv');

    if (sidSelect) {
        sidSelect.addEventListener('change', function () {
            updatePrjAndGuestLink(this.value);
        });
    }

    if (copyGuestBtn) {
        copyGuestBtn.addEventListener('click', copyGuestLink);
    }

    if (copyLinksBtn) {
        copyLinksBtn.addEventListener('click', copyLinks);
    }

    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', exportCSV);
    }
}

function bindRightSideActions() {
    const sidRight = document.getElementById('sidRight');
    const refreshBtn = document.getElementById('btn-refresh-results');
    const enableBtn = document.getElementById('btn-enable-uids');
    const resetBtn = document.getElementById('btn-reset-iids');
    const searchBtn = document.getElementById('btn-search-record');

    if (sidRight) {
        sidRight.addEventListener('change', function () {
            updatePrjRight(this.value);
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshResults);
    }

    if (enableBtn) {
        enableBtn.addEventListener('click', enableUids);
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', resetIids);
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchUidIidRecord);
    }
}

function bindDeletePanelButtons() {
    const buttons = document.querySelectorAll('.btn-delete-panel');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const id = this.dataset.panelId;
            if (id) {
                deletePanel(id);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
        bindLeftSideActions();
        bindRightSideActions();
        bindDeletePanelButtons();
        bindAddPanelForm();
        bindGenerateFormLoading();
        bindSearchEnter();
        bindUidInputWatcher();
        initRightPanelState();

    const sidLeft = document.getElementById('sid');
    if (sidLeft) {
        updatePrjAndGuestLink(sidLeft.value);
    }
});



})();


