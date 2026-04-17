document.addEventListener('DOMContentLoaded', () => {
    if (window.feather) {
        feather.replace();
    }

    const selRicerca = document.getElementById('ricerca');
    const selTarget = document.getElementById('target');
    const btnAdd = document.getElementById('btn-add-campione');
    const campioneCard = document.getElementById('campione-card');
    const listEl = document.getElementById('sottocampioni-list');
    const resultsBox = document.getElementById('disponibili-results');
    const btnCrea = document.getElementById('btn-crea-campione');
    const chkFollowup = document.getElementById('chk-followup');
    const modeStd = document.getElementById('mode_std');
    const modeFu = document.getElementById('mode_fu');

    const MAX = 3;
    let CAMPIONE_FINAL = false;

    window.sottocampioni = window.sottocampioni || [];

    const labels = {
        sesso: 'Sesso',
        eta: 'Età',
        regioni: 'Regione',
        aree: 'Area',
        province: 'Province',
        ampiezza: 'Ampiezza',
        iscritto_dal: 'Iscritto dal',
        livello_attivita: 'Livello attività',
        target: 'Target',
        exclude: 'Escludi Ricerche'
    };

    function syncFollowupFromSeg() {
        if (!chkFollowup) {
            return;
        }

        chkFollowup.checked = !!(modeFu && modeFu.checked);
    }

    if (modeStd) {
        modeStd.addEventListener('change', syncFollowupFromSeg);
    }

    if (modeFu) {
        modeFu.addEventListener('change', syncFollowupFromSeg);
    }

    syncFollowupFromSeg();

    function updateRicercaState() {
        const hasRicerca = !!(selRicerca && selRicerca.value);

        if (selTarget) {
            selTarget.disabled = !hasRicerca;
        }

        if (btnAdd) {
            btnAdd.disabled = !hasRicerca || window.sottocampioni.length >= MAX || CAMPIONE_FINAL;
        }
    }

    function clearDisponibili() {
        if (!resultsBox) {
            return;
        }

        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
    }

    async function applyPanelDefaultsByRicerca(surId) {
        const uomo = document.getElementById('sUomo');
        const donna = document.getElementById('sDonna');
        const etaDa = document.getElementById('eta_da');
        const etaA = document.getElementById('eta_a');

        if (!surId) {
            if (uomo) uomo.checked = false;
            if (donna) donna.checked = false;
            if (etaDa) etaDa.value = '';
            if (etaA) etaA.value = '';
            return;
        }

        try {
            const url = window.campionamentoUrls.panelDataTemplate.replace('__ID__', encodeURIComponent(surId));

            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                return;
            }

            const data = await res.json();
            const sex = parseInt(data.sex_target, 10);

            if (uomo) uomo.checked = (sex === 1 || sex === 3);
            if (donna) donna.checked = (sex === 2 || sex === 3);

            if (etaDa) etaDa.value = data.age1_target ?? '';
            if (etaA) etaA.value = data.age2_target ?? '';
        } catch (error) {
            console.error('applyPanelDefaultsByRicerca error', error);
        }
    }

    function renderCampione() {
        const rightPlaceholder = document.getElementById('right-placeholder');

        if (!window.sottocampioni.length) {
            if (rightPlaceholder) {
                rightPlaceholder.style.display = 'block';
            }

            if (campioneCard) {
                campioneCard.style.display = 'none';
            }

            if (listEl) {
                listEl.innerHTML = '';
            }

            clearDisponibili();
            updateRicercaState();
            return;
        }

        if (campioneCard) {
            campioneCard.style.display = 'block';
        }

        if (rightPlaceholder) {
            rightPlaceholder.style.display = 'none';
        }

        if (!listEl) {
            return;
        }

        listEl.innerHTML = '';

        window.sottocampioni.forEach((sc, i) => {
            const wrap = document.createElement('div');
            wrap.className = 'border-left pl-2 mb-2';

            wrap.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <strong>
                        Sottocampione ${i + 1}
                        <span class="text-muted ml-2">
                            <i data-feather="users" class="align-text-bottom"></i>
                            (${Number.isFinite(sc.count) ? sc.count : '…'})
                        </span>
                        ${sc.followup ? '<span class="badge badge-info ml-1">Follow-up</span>' : ''}
                    </strong>

                    <button data-index="${i}" class="btn btn-sm btn-outline-danger btn-delete">
                        <i data-feather="trash-2"></i>
                    </button>
                </div>

                <div class="mt-2">
                    <label class="small mb-1">Inviti</label>

                    <div class="input-group input-group-sm" data-index="${i}">
                        <span class="input-group-text">
                            <i data-feather="mail"></i>
                        </span>

                        <input
                            type="number"
                            class="form-control sc-invite"
                            min="1"
                            ${Number.isFinite(sc.count) ? `max="${sc.count}"` : ''}
                            value="${Number.isFinite(sc.invite) ? sc.invite : 1}"
                            ${Number.isFinite(sc.count) && !CAMPIONE_FINAL ? '' : 'disabled'}
                            inputmode="numeric"
                        >

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-max-invite"
                            ${Number.isFinite(sc.count) && !CAMPIONE_FINAL ? '' : 'disabled'}
                            title="Imposta al massimo disponibile"
                        >
                            MAX
                        </button>

                        <span class="input-group-text">/ ${Number.isFinite(sc.count) ? sc.count : '—'}</span>
                    </div>

                    <small class="text-muted">Seleziona da 1 al massimo disponibile.</small>
                </div>

                <ul class="small mb-2 mt-2" style="list-style:none;padding-left:0;">
                    ${Object.entries(sc)
                        .filter(([k, v]) => ['sesso', 'eta', 'regioni', 'aree', 'province', 'ampiezza', 'iscritto_dal', 'livello_attivita', 'target', 'exclude'].includes(k))
                        .map(([k, v]) => v ? `<li><strong>${labels[k]}:</strong> ${v}</li>` : '')
                        .join('')}
                </ul>
            `;

            listEl.appendChild(wrap);
        });

        if (window.feather) {
            feather.replace();
        }

        if (CAMPIONE_FINAL) {
            listEl.querySelectorAll('.btn-delete').forEach((btn) => btn.remove());

            if (btnCrea) {
                btnCrea.style.display = 'none';
            }

            if (btnAdd) {
                btnAdd.disabled = true;
            }
        } else {
            listEl.querySelectorAll('.btn-delete').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.dataset.index, 10);
                    window.sottocampioni.splice(idx, 1);
                    renderCampione();
                    clearDisponibili();
                    fetchCountsAndRender();
                });
            });

            if (btnCrea) {
                btnCrea.style.display = 'inline-block';
            }

            updateRicercaState();
        }

        listEl.querySelectorAll('.sc-invite').forEach((inp) => {
            const idx = parseInt(inp.closest('.input-group').dataset.index, 10);

            inp.addEventListener('input', () => {
                const sc = window.sottocampioni[idx];
                const max = Number.isFinite(sc.count) ? sc.count : 1;

                let value = parseInt(inp.value || '1', 10);

                if (isNaN(value) || value < 1) {
                    value = 1;
                }

                if (value > max) {
                    value = max;
                }

                inp.value = value;
                sc.invite = value;
            });
        });

        listEl.querySelectorAll('.btn-max-invite').forEach((btn) => {
            const idx = parseInt(btn.closest('.input-group').dataset.index, 10);

            btn.addEventListener('click', () => {
                const sc = window.sottocampioni[idx];
                const max = Number.isFinite(sc.count) ? sc.count : 1;

                sc.invite = max;

                const inp = btn.closest('.input-group').querySelector('.sc-invite');
                if (inp) {
                    inp.value = max;
                }
            });
        });
    }

    function showCountingLoading() {
        if (!resultsBox) {
            return;
        }

        resultsBox.style.display = 'block';
        resultsBox.innerHTML = `
            <div class="sv-card sv-card-right">
                <div class="sv-body">
                    <div class="sv-loading sv-loading-hero">
                        <div class="sv-loading-badge">
                            <i data-feather="activity"></i>
                        </div>

                        <div class="sv-loading-content">
                            <div class="sv-loading-title">
                                Conteggio utenti disponibili in corso
                                <span class="sv-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                            </div>
                            <div class="sv-loading-sub">
                                Sto calcolando il totale e i conteggi per sottocampione.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (window.feather) {
            feather.replace();
        }
    }

    async function fetchCountsAndRender() {
        if (!selRicerca || !selRicerca.value) {
            clearDisponibili();
            return;
        }

        if (!window.sottocampioni.length) {
            clearDisponibili();
            return;
        }

        showCountingLoading();

        const samples = window.sottocampioni.map((sc) => ({
            sesso: (sc.sesso || '').split('/').filter(Boolean),
            eta_da: sc.eta ? parseInt(sc.eta.split('-')[0], 10) : null,
            eta_a: sc.eta ? parseInt(sc.eta.split('-')[1], 10) : null,
            regioni: sc.regioni ? sc.regioni.split(',').map((s) => s.trim()).filter(Boolean) : [],
            aree: sc.aree ? sc.aree.split(',').map((s) => s.trim()).filter(Boolean) : [],
            province_id: sc.province ? sc.province.split(',').map((s) => s.trim()).filter(Boolean) : [],
            ampiezza: sc.ampiezza ? sc.ampiezza.split(',').map((s) => s.trim()).filter(Boolean) : [],
            iscritto_dal: sc.iscritto_dal ? parseInt(sc.iscritto_dal, 10) : null,
            target_id: sc.target_id || null,
            invite: Number.isFinite(sc.count) ? Math.min(sc.invite || 1, sc.count) : (sc.invite || 1),
            followup: sc.followup || false
        }));

        const excludeCodes = (document.getElementById('exclude_ricerche')?.value || '').trim();
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const res = await fetch(window.campionamentoUrls.utentiDisponibili, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                    body: JSON.stringify({
                        sur_id: selRicerca.value,
                        exclude_codes: excludeCodes,
                        samples: samples,
                        debug: true
                    })
            });

            const raw = await res.text();
            let data;

            try {
                data = JSON.parse(raw);
            } catch (error) {
                console.error(raw);
                return;
            }

            if (Array.isArray(data.items)) {
                data.items.forEach((item, i) => {
                    if (window.sottocampioni[i]) {
                        window.sottocampioni[i].count = item.count;
                    }
                });
            }

            window.sottocampioni.forEach((sc) => {
                if (!Number.isFinite(sc.count)) {
                    return;
                }

                if (!Number.isFinite(sc.invite)) {
                    sc.invite = 1;
                }

                if (sc.invite < 1) {
                    sc.invite = 1;
                }

                if (sc.invite > sc.count) {
                    sc.invite = sc.count;
                }
            });

            renderCampione();
            renderTotale(data.total);
        } catch (error) {
            console.error(error);
        }
    }

    function renderTotale(total) {
        if (!resultsBox) {
            return;
        }

        if (typeof total !== 'number') {
            clearDisponibili();
            return;
        }

        resultsBox.innerHTML = `
            <div class="sv-card sv-card-right sv-total-card">
                <div class="sv-card-header">
                    <div class="sv-head">
                        <div class="sv-head-left">
                            <span class="sv-total-icon">
                                <i data-feather="users"></i>
                            </span>
                            <h6 class="sv-title">Totale disponibili</h6>
                        </div>
                    </div>
                </div>

                <div class="sv-body">
                    <div class="sv-total">
                        <div class="sv-total-number sv-pop">${total}</div>
                    </div>
                </div>
            </div>
        `;

        resultsBox.style.display = 'block';

        if (window.feather) {
            feather.replace();
        }
    }

    function renderCsvResult(data) {
        const box = document.getElementById('crea-campione-results');

        if (!box) {
            return;
        }

        const count = data.enabled_count || 0;
        const filename = data.filename || 'campione.csv';
        const csvText = data.csv_text || '';
        const csvB64 = data.csv_base64 || '';

        box.innerHTML = `
            <div class="card shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                        <div class="small text-muted">Utenti abilitati</div>
                        <div class="h3 mb-0">${count}</div>
                    </div>

                    <div class="d-flex align-items-center">
                        <button id="btn-download-csv" class="btn btn-sm btn-primary mr-2">
                            <i data-feather="download"></i> Download CSV
                        </button>

                        <button id="btn-copy-csv" class="btn btn-sm btn-outline-secondary mr-2">
                            <i data-feather="copy"></i> Copia contenuto
                        </button>

                        <button id="btn-clear-all" class="btn btn-sm btn-outline-danger">
                            <i data-feather="x-circle"></i> Pulisci
                        </button>
                    </div>
                </div>
            </div>
        `;

        box.style.display = 'block';

        if (window.feather) {
            feather.replace();
        }

        const btnDl = document.getElementById('btn-download-csv');
        if (btnDl) {
            btnDl.addEventListener('click', () => {
                const bytes = atob(csvB64);
                const arr = new Uint8Array(bytes.length);

                for (let i = 0; i < bytes.length; i++) {
                    arr[i] = bytes.charCodeAt(i);
                }

                const blob = new Blob([arr], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');

                a.href = url;
                a.download = filename;

                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                URL.revokeObjectURL(url);
            });
        }

        const btnCopy = document.getElementById('btn-copy-csv');
        if (btnCopy) {
            btnCopy.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(csvText);

                    btnCopy.classList.remove('btn-outline-secondary');
                    btnCopy.classList.add('btn-success');
                    btnCopy.innerHTML = '<i data-feather="check"></i> Copiato';

                    if (window.feather) {
                        feather.replace();
                    }

                    setTimeout(() => {
                        btnCopy.classList.remove('btn-success');
                        btnCopy.classList.add('btn-outline-secondary');
                        btnCopy.innerHTML = '<i data-feather="copy"></i> Copia contenuto';

                        if (window.feather) {
                            feather.replace();
                        }
                    }, 2000);
                } catch (error) {
                    alert('Impossibile copiare negli appunti.');
                }
            });
        }

        const btnClear = document.getElementById('btn-clear-all');
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                const res1 = document.getElementById('disponibili-results');
                const res2 = document.getElementById('crea-campione-results');

                if (res1) {
                    res1.innerHTML = '';
                    res1.style.display = 'none';
                }

                if (res2) {
                    res2.innerHTML = '';
                    res2.style.display = 'none';
                }

                window.sottocampioni = [];
                CAMPIONE_FINAL = false;

                const form = document.getElementById('campionamentoForm');
                if (form) {
                    form.reset();
                }

                if (window.SvMultiSelect && typeof window.SvMultiSelect.syncAll === 'function') {
                    window.SvMultiSelect.syncAll();
                }

                syncFollowupFromSeg();
                clearDisponibili();
                renderCampione();
                updateRicercaState();
            });
        }
    }

    updateRicercaState();

    if (selRicerca) {
            selRicerca.addEventListener('change', async () => {
                updateRicercaState();

                await applyPanelDefaultsByRicerca(selRicerca.value);

                window.sottocampioni = [];

                const creaBox = document.getElementById('crea-campione-results');
                if (creaBox) {
                    creaBox.innerHTML = '';
                    creaBox.style.display = 'none';
                }

                CAMPIONE_FINAL = false;

                clearDisponibili();
                renderCampione();
                updateRicercaState();
            });
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            if (CAMPIONE_FINAL) {
                alert('Campione già generato. Usa "Pulisci" per ricominciare.');
                return;
            }

            if (!selRicerca || !selRicerca.value) {
                alert('Seleziona prima una Ricerca.');
                return;
            }

            if (window.sottocampioni.length >= MAX) {
                return;
            }

            const sesso = Array.from(document.querySelectorAll('input[name="sesso[]"]:checked'))
                .map((cb) => cb.value)
                .join('/');

            const etaDa = document.getElementById('eta_da').value || '';
            const etaA = document.getElementById('eta_a').value || '';
            const eta = (etaDa && etaA) ? `${etaDa}-${etaA}` : '';

            const regioni = Array.from(document.getElementById('regioni').selectedOptions).map((o) => o.value).join(', ');
            const aree = Array.from(document.getElementById('aree').selectedOptions).map((o) => o.value).join(', ');
            const province = Array.from(document.getElementById('province_id').selectedOptions).map((o) => o.value).join(', ');
            const ampiezza = Array.from(document.getElementById('ampiezza').selectedOptions).map((o) => o.value).join(', ');

            const iscrittoDal = document.getElementById('iscritto_dal').value || '';
            const livelloAttivita = document.getElementById('livello_attivita').value || '';

            const targetText = selTarget && selTarget.selectedIndex >= 0
                ? selTarget.options[selTarget.selectedIndex].text.trim()
                : '';

            const targetId = selTarget && selTarget.value
                ? parseInt(selTarget.value, 10)
                : null;

            const excludeEl = document.getElementById('exclude_ricerche');
            const exclude = excludeEl ? excludeEl.value.trim() : '';

            const sc = {
                sesso: sesso,
                eta: eta,
                regioni: regioni,
                aree: aree,
                province: province,
                ampiezza: ampiezza,
                iscritto_dal: iscrittoDal,
                livello_attivita: livelloAttivita,
                target: targetText,
                target_id: targetId,
                exclude: exclude,
                invite: 1,
                followup: chkFollowup?.checked || false
            };

            window.sottocampioni.push(sc);
            renderCampione();
            fetchCountsAndRender();
        });
    }

    if (btnCrea) {
        btnCrea.addEventListener('click', async () => {
            if (!selRicerca || !selRicerca.value) {
                alert('Seleziona prima una Ricerca.');
                return;
            }

            if (!window.sottocampioni.length) {
                alert('Aggiungi almeno un sottocampione.');
                return;
            }

            const samples = window.sottocampioni.map((sc) => ({
                sesso: (sc.sesso || '').split('/').filter(Boolean),
                eta_da: sc.eta ? parseInt(sc.eta.split('-')[0], 10) : null,
                eta_a: sc.eta ? parseInt(sc.eta.split('-')[1], 10) : null,
                regioni: sc.regioni ? sc.regioni.split(',').map((s) => s.trim()).filter(Boolean) : [],
                aree: sc.aree ? sc.aree.split(',').map((s) => s.trim()).filter(Boolean) : [],
                province_id: sc.province ? sc.province.split(',').map((s) => s.trim()).filter(Boolean) : [],
                ampiezza: sc.ampiezza ? sc.ampiezza.split(',').map((s) => s.trim()).filter(Boolean) : [],
                iscritto_dal: sc.iscritto_dal ? parseInt(sc.iscritto_dal, 10) : null,
                target_id: sc.target_id || null,
                invite: Number.isFinite(sc.count) ? Math.min(sc.invite || 1, sc.count) : (sc.invite || 1),
                followup: sc.followup || false
            }));

            const excludeCodes = (document.getElementById('exclude_ricerche')?.value || '').trim();
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const res = await fetch(window.campionamentoUrls.creaCampioni, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sur_id: selRicerca.value,
                        exclude_codes: excludeCodes,
                        samples: samples
                    })
                });

                const raw = await res.text();
                let data;

                try {
                    data = JSON.parse(raw);
                } catch (error) {
                    console.error(raw);
                    alert('Risposta non valida');
                    return;
                }

                renderCsvResult(data);
                CAMPIONE_FINAL = true;
                renderCampione();
            } catch (error) {
                console.error(error);
                alert('Errore durante la creazione del campione.');
            }
        });
    }

    renderCampione();
});
