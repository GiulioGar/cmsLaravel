/* ==========================================================================
   Panoramica - analisi field domanda per domanda
   ========================================================================== */
(function () {
    'use strict';

    var config = window.PanoramicaConfig || {};
    var currentRequest = 0;
    var currentQuestionId = null;
    var currentCross = '';
    var trendMode = 'daily';
    var lastData = null;
    var trendChart = null;

    var TYPE_LABELS = { choice: 'Scelta', scale: 'Griglia', open: 'Aperta' };
    var SERIES_COLORS = ['#2563eb', '#16a34a', '#f59e0b', '#db2777', '#0891b2', '#7c3aed',
        '#ea580c', '#65a30d', '#475569', '#dc2626', '#0d9488', '#9333ea'];
    // Sotto questa base l'incrocio non viene evidenziato
    var LOW_BASE = 30;
    // Sotto questa base giornaliera il dato del giorno e' mostrato in trasparenza
    var LOW_DAY_BASE = 10;
    var DELTA_THRESHOLD = 5;

    function $(id) { return document.getElementById(id); }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtInt(n) { return Number(n || 0).toLocaleString('it-IT'); }

    function pct(part, base) { return base > 0 ? (part / base) * 100 : 0; }

    function fmtPct(value) {
        return value.toLocaleString('it-IT', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
    }

    function fmtNum(value) { return Number(value).toLocaleString('it-IT', { maximumFractionDigits: 1 }); }

    function labelOf(list, idx, prefix) {
        return list && list[idx] != null && list[idx] !== '' ? list[idx] : prefix + ' ' + (idx + 1);
    }

    function emptyInline(text) {
        return '<div class="pn-empty pn-empty--inline"><i class="fa-solid fa-robot"></i><span>' + esc(text) + '</span></div>';
    }

    /* ---------------------------------------------------------------
       Sidebar: ricerca + filtro "Con uscite"
    --------------------------------------------------------------- */
    var activeFilter = 'all';

    function applySidebarFilter() {
        var term = ($('pnQuestionSearch').value || '').trim().toLowerCase();
        var items = document.querySelectorAll('.pn-qitem');
        var visible = 0;

        items.forEach(function (item) {
            var matchText = term === '' || item.getAttribute('data-search').indexOf(term) !== -1;
            var matchFilter = activeFilter === 'all' || parseInt(item.getAttribute('data-exits'), 10) > 0;
            var show = matchText && matchFilter;
            item.classList.toggle('d-none', !show);
            if (show) { visible++; }
        });

        // Famiglie loop: visibili se almeno un elemento lo e'; aperte quando si filtra
        var filtering = term !== '' || activeFilter !== 'all';
        document.querySelectorAll('.pn-qfamily').forEach(function (family) {
            var anyVisible = family.querySelector('.pn-qitem:not(.d-none)') !== null;
            family.classList.toggle('d-none', !anyVisible);
            if (filtering && anyVisible) { setFamilyOpen(family, true); }
        });

        $('pnNoMatch').classList.toggle('d-none', visible > 0 || items.length === 0);
    }

    function setFamilyOpen(family, open) {
        family.classList.toggle('is-open', open);
        family.querySelector('.pn-qfamily-head').setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function initSidebar() {
        $('pnQuestionSearch').addEventListener('input', applySidebarFilter);

        document.querySelectorAll('.pn-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.pn-filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                activeFilter = btn.getAttribute('data-filter');
                applySidebarFilter();
            });
        });

        document.querySelectorAll('.pn-qitem').forEach(function (item) {
            item.addEventListener('click', function () {
                selectQuestion(parseInt(item.getAttribute('data-question-id'), 10));
            });
        });

        document.querySelectorAll('.pn-qfamily-head').forEach(function (head) {
            head.addEventListener('click', function () {
                var family = head.parentNode;
                setFamilyOpen(family, !family.classList.contains('is-open'));
            });
        });
    }

    /* ---------------------------------------------------------------
       Caricamento domanda
    --------------------------------------------------------------- */
    function selectQuestion(questionId) {
        currentQuestionId = questionId;
        syncCrossSelect();

        document.querySelectorAll('.pn-qitem').forEach(function (item) {
            var isActive = parseInt(item.getAttribute('data-question-id'), 10) === questionId;
            item.classList.toggle('is-active', isActive);
            var family = item.closest('.pn-qfamily');
            if (isActive && family) { setFamilyOpen(family, true); }
        });

        try {
            history.replaceState(null, '', '#q=' + questionId + (currentCross ? '&x=' + encodeURIComponent(currentCross) : ''));
        } catch (e) { /* ignore */ }

        var requestId = ++currentRequest;
        $('pnLoading').classList.remove('d-none');

        var url = config.questionUrl
            + '?prj=' + encodeURIComponent(config.prj)
            + '&sid=' + encodeURIComponent(config.sid)
            + '&question_id=' + encodeURIComponent(questionId)
            + (currentCross ? '&cross=' + encodeURIComponent(currentCross) : '');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (requestId !== currentRequest) { return; }
                if (!json.success) {
                    showError(json.message || 'Errore durante l\'analisi.');
                    return;
                }
                render(json.data);
            })
            .catch(function () {
                if (requestId === currentRequest) { showError('Errore di rete durante l\'analisi.'); }
            })
            .finally(function () {
                if (requestId === currentRequest) { $('pnLoading').classList.add('d-none'); }
            });
    }

    function showError(message) {
        $('pnPlaceholder').classList.remove('d-none');
        $('pnContent').classList.add('d-none');
        $('pnPlaceholder').querySelector('.pn-empty-title').textContent = 'Impossibile caricare la domanda';
        $('pnPlaceholder').querySelector('.pn-empty-sub').textContent = message;
    }

    /* ---------------------------------------------------------------
       Render
    --------------------------------------------------------------- */
    function render(data) {
        var q = data.question;
        lastData = data;

        $('pnPlaceholder').classList.add('d-none');
        $('pnContent').classList.remove('d-none');

        $('pnQCode').textContent = q.code;
        $('pnQType').textContent = (TYPE_LABELS[q.type] || q.type) + (data.is_multiple ? ' multipla' : (q.type === 'choice' ? ' singola' : ''));
        $('pnQText').textContent = q.text;
        $('pnQCtx').textContent = q.context ? (q.loop ? 'Ciclo: ' : '') + q.context : '';
        $('pnQCtx').classList.toggle('d-none', !q.context);

        $('pnQStats').innerHTML =
            qstat(data.reached.total, 'arrivati alla domanda', 'fas fa-users', '') +
            qstat(data.reached.complete, 'complete', 'fas fa-circle-check', 'complete') +
            (data.exit_counts.screenout > 0 ? qstat(data.exit_counts.screenout, 'screenout qui', 'fas fa-bullseye', 'screenout') : '') +
            (data.exit_counts.quotafull > 0 ? qstat(data.exit_counts.quotafull, 'quotafull qui', 'fas fa-arrow-trend-up', 'quotafull') : '');

        renderComplete(data);
        renderExits(data);
        renderCross(data);
        renderTrend(data);
    }

    function qstat(value, label, icon, variant) {
        return '<span class="pn-qstat' + (variant ? ' pn-qstat--' + variant : '') + '"><i class="' + icon + '"></i><b>' + fmtInt(value) + '</b>' + esc(label) + '</span>';
    }

    function renderComplete(data) {
        var q = data.question;
        var dist = data.complete;
        $('pnCompleteNote').textContent = 'Base: ' + fmtInt(dist.base);
        $('pnCompleteNote').title = 'Interviste complete che hanno risposto alla domanda';

        if (dist.base === 0) {
            $('pnCompleteBody').innerHTML = emptyInline('Nessuna intervista completa ha ancora risposto a questa domanda');
            return;
        }
        $('pnCompleteBody').innerHTML =
            (data.is_multiple ? '<div class="pn-card-foot pn-card-foot--top">Risposta multipla: il totale delle percentuali può superare il 100%</div>' : '')
            + renderDistribution(q, dist, '');
    }

    function renderDistribution(q, dist, variant) {
        if (q.type === 'choice') { return renderBars(q.options, dist.counts, dist.base, variant); }
        if (q.type === 'scale') { return renderGrid(q, dist, variant); }
        return renderOpen(dist);
    }

    function renderBars(labels, counts, base, variant) {
        var max = Math.max.apply(null, counts.concat([0]));
        var html = '<div class="pn-bars' + (variant ? ' pn-bars--' + variant : '') + '">';

        counts.forEach(function (count, idx) {
            // Nelle uscite contano solo le risposte che le hanno generate
            if (variant && count === 0) { return; }
            var value = pct(count, base);
            var cls = 'pn-bar' + (count > 0 && count === max ? ' is-top' : '') + (count === 0 ? ' is-zero' : '');
            html += '<div class="' + cls + '">'
                + '<div class="pn-bar-label">' + esc(labelOf(labels, idx, 'Opzione')) + '</div>'
                + '<div class="pn-bar-track"><div class="pn-bar-fill" style="width:' + Math.min(100, value).toFixed(2) + '%"></div></div>'
                + '<div class="pn-bar-val"><span class="pn-bar-pct">' + fmtPct(value) + '</span><span class="pn-bar-n">' + fmtInt(count) + '</span></div>'
                + '</div>';
        });

        return html + '</div>';
    }

    var GRID_COLORS = {
        '': '22,163,74',
        screenout: '234,88,12',
        quotafull: '220,38,38'
    };

    function renderGrid(q, dist, variant) {
        var rgb = GRID_COLORS[variant || ''];
        var cols = q.cols || [];
        var rows = q.rows || [];

        var html = '<div class="pn-grid-wrap"><table class="pn-grid"><thead><tr><th class="pn-grid-rowhead"></th>';
        cols.forEach(function (col, cIdx) {
            html += '<th>' + esc(labelOf(cols, cIdx, 'Col.')) + '</th>';
        });
        html += '<th>Base</th></tr></thead><tbody>';

        dist.counts.forEach(function (rowCounts, rIdx) {
            var rowBase = dist.row_bases[rIdx] || 0;
            html += '<tr><td class="pn-grid-rowhead">' + esc(labelOf(rows, rIdx, 'Riga')) + '</td>';
            rowCounts.forEach(function (count) {
                var value = pct(count, rowBase);
                var alpha = rowBase > 0 ? (0.06 + (value / 100) * 0.6).toFixed(3) : 0;
                var light = value >= 55;
                html += '<td style="background:rgba(' + rgb + ',' + alpha + ');' + (light ? 'color:#fff' : '') + '">'
                    + (rowBase > 0 ? fmtPct(value) : '–')
                    + '<small' + (light ? ' style="color:rgba(255,255,255,.85)"' : '') + '>' + fmtInt(count) + '</small></td>';
            });
            html += '<td class="pn-grid-base">' + fmtInt(rowBase) + '</td></tr>';
        });

        return html + '</tbody></table></div>';
    }

    function renderOpen(dist) {
        var html = '';
        if (dist.stats) {
            var s = dist.stats;
            html += '<div class="pn-stats">'
                + stat('Risposte numeriche', fmtInt(s.count))
                + stat('Media', fmtNum(s.mean))
                + stat('Mediana', fmtNum(s.median))
                + stat('Minimo', fmtNum(s.min))
                + stat('Massimo', fmtNum(s.max))
                + '</div>';
        }
        if (dist.non_numeric > 0) {
            html += '<div class="pn-info"><i class="fas fa-pen me-1"></i>' + fmtInt(dist.non_numeric)
                + ' risposte testuali (non numeriche) su ' + fmtInt(dist.base) + '.</div>';
        }
        return html || emptyInline('Nessuna risposta');
    }

    function stat(key, value) {
        return '<div class="pn-stat"><div class="pn-stat-key">' + esc(key) + '</div><div class="pn-stat-val">' + value + '</div></div>';
    }

    /* ---------------------------------------------------------------
       Uscite: box solo per gli stati con almeno un'uscita
    --------------------------------------------------------------- */
    function renderExits(data) {
        var card = $('pnExitCard');
        var visible = 0;

        ['screenout', 'quotafull'].forEach(function (status) {
            var el = $(status === 'screenout' ? 'pnExitScreenout' : 'pnExitQuotafull');
            var hasExits = data.exit_counts[status] > 0;
            el.classList.toggle('d-none', !hasExits);
            if (hasExits) {
                renderExit(el, status, data);
                visible++;
            }
        });

        $('pnExitBase').textContent = 'Base: ' + fmtInt(data.reached.total);
        $('pnExitBase').title = 'Interviste arrivate alla domanda';
        card.classList.toggle('d-none', visible === 0);

        // Affiancati solo se ci sono uscite e la domanda non e' una griglia (tabelle larghe)
        $('pnDuo').classList.toggle('is-split', visible > 0 && data.question.type !== 'scale');
    }

    function renderExit(el, status, data) {
        var isScreenout = status === 'screenout';
        var title = isScreenout ? 'screenout' : 'quotafull';
        var count = data.exit_counts[status];
        var totalStatus = data.totals[status];
        var dist = data[status];

        el.className = 'pn-exit pn-exit--' + status;
        var html = '<div class="pn-exit-kpis">'
            + '<div class="pn-exit-main"><span class="pn-exit-main-val">' + fmtInt(count) + '</span><span class="pn-exit-main-key">' + title + '</span></div>'
            + exitKpi(fmtPct(pct(count, data.reached.total)), 'degli arrivati alla domanda (' + fmtInt(data.reached.total) + ')')
            + exitKpi(fmtPct(pct(count, totalStatus)), 'del totale ' + title + ' (' + fmtInt(totalStatus) + ')')
            + '</div>';

        if (dist.base > 0) {
            html += '<div class="pn-exit-sub">Risposta che genera l\'uscita</div>'
                + renderDistribution(data.question, dist, status);
        } else {
            html += emptyInline('Uscite senza una risposta registrata alla domanda');
        }

        el.innerHTML = html;
    }

    function exitKpi(value, label) {
        return '<div class="pn-exit-kpi"><span class="pn-exit-kpi-val">' + value + '</span><span class="pn-exit-kpi-key">' + esc(label) + '</span></div>';
    }

    /* ---------------------------------------------------------------
       Incrocio
    --------------------------------------------------------------- */
    function syncCrossSelect() {
        var select = $('pnCrossSelect');
        Array.prototype.forEach.call(select.querySelectorAll('option[data-question-id]'), function (opt) {
            opt.disabled = parseInt(opt.getAttribute('data-question-id'), 10) === currentQuestionId;
        });
        // Non si incrocia una domanda con se stessa
        if (currentCross === 'q:' + currentQuestionId) {
            currentCross = '';
        }
        select.value = currentCross;
        select.classList.toggle('is-set', currentCross !== '');
        $('pnCrossClear').classList.toggle('d-none', currentCross === '');
    }

    function initCross() {
        $('pnCrossSelect').addEventListener('change', function () {
            currentCross = this.value;
            if (currentQuestionId !== null) { selectQuestion(currentQuestionId); }
        });
        $('pnCrossClear').addEventListener('click', function () {
            currentCross = '';
            if (currentQuestionId !== null) { selectQuestion(currentQuestionId); }
        });

        // Tabella | Grafico: solo cambio di rendering sui dati gia' caricati
        document.querySelectorAll('#pnCrossView .pn-seg-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                crossView = btn.getAttribute('data-view');
                try { localStorage.setItem('panoramica.crossView', crossView); } catch (e) { /* ignore */ }
                syncCrossViewButtons();
                if (lastData) { renderCross(lastData); }
            });
        });
        try {
            if (localStorage.getItem('panoramica.crossView') === 'chart') { crossView = 'chart'; }
        } catch (e) { /* ignore */ }
        syncCrossViewButtons();
    }

    var crossView = 'table';

    function syncCrossViewButtons() {
        document.querySelectorAll('#pnCrossView .pn-seg-btn').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-view') === crossView);
        });
    }

    function renderCross(data) {
        var cross = data.cross;
        var tools = $('pnCrossTools');
        var sub = $('pnCrossSub');

        disposeCrossCharts();

        if (!cross) {
            tools.classList.add('d-none');
            sub.classList.add('d-none');
            $('pnCrossBody').innerHTML = '<div class="pn-cross-hint"><i class="fas fa-shuffle"></i>'
                + '<span>Scegli una variabile o una domanda a scelta per confrontare le risposte delle complete tra gruppi diversi.</span></div>';
            return;
        }

        $('pnCrossCount').textContent = fmtInt(cross.columns.length) + (cross.columns.length === 1 ? ' gruppo' : ' gruppi');
        tools.classList.remove('d-none');
        sub.classList.remove('d-none');
        $('pnCrossTitle').textContent = 'Confronto per ' + cross.label;

        var note = 'Base: ' + fmtInt(data.complete.base) + ' (solo complete)';
        if (cross.missing > 0) {
            note += ' · ' + fmtInt(cross.missing) + ' senza dato di incrocio escluse';
        }
        $('pnCrossNote').textContent = note;

        if (!cross.columns.length) {
            $('pnCrossBody').innerHTML = emptyInline('Nessuna intervista completa con dato di incrocio');
            return;
        }

        if (crossView === 'chart') {
            renderCrossChartView(data);
        } else {
            renderCrossTableView(data);
        }
    }

    function disposeCrossCharts() {
        if (crossChart) { crossChart.dispose(); crossChart = null; }
        if (crossChoiceChart) { crossChoiceChart.dispose(); crossChoiceChart = null; }
    }

    /* Vista Tabella: gruppi in riga, risposte in colonna */
    function renderCrossTableView(data) {
        var q = data.question;
        var cross = data.cross;

        if (q.type === 'choice') {
            $('pnCrossBody').innerHTML = crossTable(q.options, data.complete.counts, data.complete.base, cross, function (dist) {
                return { counts: dist.counts, base: dist.base };
            }, data.is_multiple);
            return;
        }

        if (q.type === 'scale') {
            renderCrossScaleTable(data);
            return;
        }

        $('pnCrossBody').innerHTML = crossOpenTable(data);
    }

    function renderCrossScaleTable(data) {
        var q = data.question;
        var stateKey = q.id + '|' + currentCross;
        if (crossScaleState.key !== stateKey) {
            crossScaleState = { key: stateKey, mode: data.complete.counts.length > 1 ? 'group' : 'row', group: 1, row: firstAnsweredRow(data.complete) };
        }
        var row = crossScaleState.row;
        var html = '';

        if (data.complete.counts.length > 1) {
            html += '<div class="pn-xs-controls"><span class="pn-onav-caption">Riga della griglia</span><select class="pn-select" id="pnXsTableRow">';
            data.complete.counts.forEach(function (_, idx) {
                if ((data.complete.row_bases[idx] || 0) === 0) { return; }
                html += '<option value="' + idx + '"' + (idx === row ? ' selected' : '') + '>' + esc(labelOf(q.rows, idx, 'Riga')) + '</option>';
            });
            html += '</select></div>';
        }

        // Scale con molti punti (slider 0-100): colonne illeggibili, si confronta la media
        if ((q.cols || []).length > MAX_STACK_COLS) {
            html += crossScaleMeanTable(data, row);
        } else {
            html += crossTable(q.cols, data.complete.counts[row], data.complete.row_bases[row], data.cross, function (dist) {
                return { counts: dist.counts[row], base: dist.row_bases[row] };
            }, false);
        }

        $('pnCrossBody').innerHTML = html;
        var picker = $('pnXsTableRow');
        if (picker) {
            picker.addEventListener('change', function () {
                crossScaleState.row = parseInt(this.value, 10);
                renderCrossScaleTable(data);
            });
        }
    }

    function crossScaleMeanTable(data, row) {
        var q = data.question;
        var values = columnValues(q);
        var groups = [{ label: 'Totale', isTotal: true, dist: data.complete }].concat(
            data.cross.columns.map(function (col) { return { label: col.label, header: groupHeader(col, data.cross), dist: col.dist }; })
        );

        var html = '<div class="table-responsive pn-xtab-wrap"><table class="table table-sm pn-xtab mb-0"><thead><tr>'
            + '<th class="pn-xtab-rowhead">Gruppo</th><th class="pn-xtab-num">Media</th><th class="pn-xtab-num pn-xtab-basecol">Base</th></tr></thead><tbody>';
        groups.forEach(function (g) {
            var mean = rowMean(g.dist, row, values);
            var base = g.dist.row_bases[row] || 0;
            html += '<tr class="' + (g.isTotal ? 'is-total' : '') + '"><td class="pn-xtab-rowhead">' + (g.header || '<b>' + esc(g.label) + '</b>') + '</td>'
                + '<td class="pn-xtab-num"><span class="pn-cell-pct">' + (mean === null ? '–' : fmtNum(mean)) + '</span></td>'
                + '<td class="pn-xtab-num pn-xtab-basecol">' + fmtInt(base) + (!g.isTotal && base > 0 && base < LOW_BASE ? ' *' : '') + '</td></tr>';
        });
        return html + '</tbody></table></div>'
            + '<div class="pn-legend-note"><i class="fas fa-circle-info me-1"></i>Scala con ' + q.cols.length + ' punti: per leggibilità si confronta la <b>media</b> per gruppo · * base inferiore a ' + LOW_BASE + '</div>';
    }

    /* Vista Grafico: il grafico adatto al tipo di domanda, mai insieme alla tabella */
    function renderCrossChartView(data) {
        var q = data.question;

        if (q.type === 'choice') {
            // Barre 100% solo per scelta singola con poche risposte: con la multipla
            // il totale supera il 100%, con molte risposte i segmenti diventano illeggibili
            if (!data.is_multiple && activeOptionIndexes(data).length <= MANY_OPTIONS) {
                renderCrossChoiceStack(data);
            } else {
                renderCrossChoiceFocus(data);
            }
            return;
        }

        if (q.type === 'scale') {
            renderCrossScale(data);
            return;
        }

        renderCrossOpenChart(data);
    }

    function renderCrossChoiceStack(data) {
        var q = data.question;
        var cross = data.cross;
        var options = activeOptionIndexes(data);
        var groups = [{ label: 'Totale', isTotal: true, dist: data.complete }].concat(
            cross.columns.map(function (col) { return { label: col.label, dist: col.dist }; })
        );

        $('pnCrossBody').innerHTML = '<div class="pn-xs-chart" id="pnCrossChart"></div>'
            + '<div class="pn-legend-note">Ogni barra è la composizione delle risposte nel gruppo (100% = base del gruppo) · gruppi con base inferiore a ' + LOW_BASE + ' in trasparenza</div>';

        var series = options.map(function (idx, pos) {
            var color = SERIES_COLORS[pos % SERIES_COLORS.length];
            return {
                name: labelOf(q.options, idx, 'Opzione'),
                type: 'bar', stack: 'all', barWidth: 20,
                itemStyle: { color: color, borderColor: '#fff', borderWidth: 1 },
                label: {
                    show: true, color: isDark(color) ? '#fff' : '#0f172a', fontSize: 12, fontWeight: 700,
                    formatter: function (p) { return p.value >= 6 ? fmtNum(p.value) + '%' : ''; }
                },
                data: groups.map(function (g) {
                    return {
                        value: Math.round(pct(g.dist.counts[idx] || 0, g.dist.base) * 10) / 10,
                        n: g.dist.counts[idx] || 0,
                        itemStyle: { opacity: !g.isTotal && g.dist.base < LOW_BASE ? 0.4 : 1 }
                    };
                })
            };
        });

        var categories = groups.map(function (g) {
            return (g.isTotal ? '{t|Totale}' : g.label) + ' {n|(n=' + fmtInt(g.dist.base) + ')}';
        });
        var option = stackBaseOption(categories, series, null, function (items) {
            var g = groups[items[0].dataIndex];
            var html = '<b>' + esc(g.label) + '</b> · base ' + fmtInt(g.dist.base);
            items.forEach(function (it) {
                html += '<br>' + it.marker + esc(it.seriesName) + ': <b>' + fmtNum(it.value) + '%</b> (' + fmtInt(it.data.n) + ')';
            });
            return html;
        });
        option.yAxis.axisLabel.rich = { t: { fontWeight: 800, color: '#0f172a', fontSize: 12 }, n: { color: '#94a3b8', fontSize: 11 } };

        var el = $('pnCrossChart');
        el.style.height = Math.max(200, groups.length * 34 + 70) + 'px';
        crossChart = echarts.init(el);
        crossChart.setOption(option, true);
    }

    function renderCrossOpenChart(data) {
        var cross = data.cross;
        var groups = [{ label: 'Totale', isTotal: true, stats: data.complete.stats }].concat(
            cross.columns.map(function (col) { return { label: col.label, stats: col.dist.stats }; })
        ).filter(function (g) { return g.stats; });

        if (!groups.length) {
            $('pnCrossBody').innerHTML = emptyInline('Grafico non disponibile: la domanda non ha risposte numeriche da confrontare. Usa la vista Tabella.');
            return;
        }

        $('pnCrossBody').innerHTML = '<div class="pn-xs-chart" id="pnCrossChart"></div>'
            + '<div class="pn-legend-note">Media delle risposte numeriche per gruppo · gruppi con base inferiore a ' + LOW_BASE + ' in trasparenza</div>';

        var categories = groups.map(function (g) {
            return (g.isTotal ? '{t|Totale}' : g.label) + ' {n|(n=' + fmtInt(g.stats.count) + ')}';
        });
        var option = meanBaseOption(categories, [{
            name: 'Media', type: 'bar', barWidth: 18,
            label: { show: true, position: 'right', fontWeight: 700, color: '#0f172a', formatter: function (p) { return fmtNum(p.value); } },
            data: groups.map(function (g) {
                return {
                    value: g.stats.mean,
                    itemStyle: { color: g.isTotal ? '#0f172a' : '#2563eb', borderRadius: [0, 4, 4, 0], opacity: !g.isTotal && g.stats.count < LOW_BASE ? 0.4 : 1 }
                };
            })
        }], function (items) {
            var g = groups[items[0].dataIndex];
            return '<b>' + esc(g.label) + '</b> · base ' + fmtInt(g.stats.count) + '<br>Media: <b>' + fmtNum(items[0].value) + '</b>';
        });
        option.legend.show = false;
        option.yAxis.axisLabel.rich = { t: { fontWeight: 800, color: '#0f172a', fontSize: 12 }, n: { color: '#94a3b8', fontSize: 11 } };

        var el = $('pnCrossChart');
        el.style.height = Math.max(180, groups.length * 34 + 50) + 'px';
        crossChart = echarts.init(el);
        crossChart.setOption(option, true);
    }

    /* ---------------------------------------------------------------
       Domande a scelta con molte risposte (es. regione):
       una risposta alla volta + sintesi delle differenze principali
    --------------------------------------------------------------- */
    var MANY_OPTIONS = 6;
    var crossChoiceState = { key: '', option: 0 };
    var crossChoiceChart = null;

    function activeOptionIndexes(data) {
        return data.complete.counts
            .map(function (c, idx) { return c > 0 ? idx : -1; })
            .filter(function (idx) { return idx >= 0; });
    }

    function mostFrequentOption(data) {
        var best = 0;
        data.complete.counts.forEach(function (c, idx) { if (c > data.complete.counts[best]) { best = idx; } });
        return best;
    }

    /**
     * Selettore "una voce alla volta": frecce + menu a tendina.
     * onChange riceve il nuovo indice.
     */
    function optionNavigatorHtml(id, caption, labels, indexes, active) {
        var pos = indexes.indexOf(active);
        var html = '<div class="pn-onav" id="' + id + '">'
            + '<span class="pn-onav-caption">' + esc(caption) + '</span>'
            + '<button type="button" class="pn-xs-arrow" data-onav-step="-1" title="Precedente"' + (pos <= 0 ? ' disabled' : '') + '><i class="fas fa-chevron-left"></i></button>'
            + '<select class="pn-select">';
        indexes.forEach(function (idx) {
            html += '<option value="' + idx + '"' + (idx === active ? ' selected' : '') + '>' + esc(labelOf(labels, idx, 'Opzione')) + '</option>';
        });
        return html + '</select>'
            + '<button type="button" class="pn-xs-arrow" data-onav-step="1" title="Successiva"' + (pos >= indexes.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-right"></i></button>'
            + '<span class="pn-onav-pos">' + (pos + 1) + ' di ' + indexes.length + '</span>'
            + '</div>';
    }

    function bindOptionNavigator(id, indexes, active, onChange) {
        var nav = $(id);
        if (!nav) { return; }
        nav.querySelector('select').addEventListener('change', function () { onChange(parseInt(this.value, 10)); });
        nav.querySelectorAll('[data-onav-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var pos = indexes.indexOf(active) + parseInt(btn.getAttribute('data-onav-step'), 10);
                if (pos >= 0 && pos < indexes.length) { onChange(indexes[pos]); }
            });
        });
    }

    function crossDeviations(data) {
        var list = [];
        var totalBase = data.complete.base;
        data.cross.columns.forEach(function (col) {
            if (col.dist.base < LOW_BASE) { return; }
            col.dist.counts.forEach(function (count, idx) {
                var totalPct = pct(data.complete.counts[idx], totalBase);
                var value = pct(count, col.dist.base);
                var delta = value - totalPct;
                if (Math.abs(delta) >= DELTA_THRESHOLD) {
                    list.push({ option: idx, group: col.label, value: value, total: totalPct, delta: delta });
                }
            });
        });
        return list.sort(function (a, b) { return Math.abs(b.delta) - Math.abs(a.delta); });
    }

    function renderCrossChoiceFocus(data) {
        var q = data.question;
        var cross = data.cross;
        var indexes = activeOptionIndexes(data);
        var stateKey = q.id + '|' + currentCross;
        if (crossChoiceState.key !== stateKey || indexes.indexOf(crossChoiceState.option) === -1) {
            crossChoiceState = { key: stateKey, option: mostFrequentOption(data) };
        }
        var option = crossChoiceState.option;

        // Sintesi: le differenze piu' marcate rispetto al totale
        var deviations = crossDeviations(data).slice(0, 5);
        var html = '<div class="pn-summary"><div class="pn-summary-title"><i class="fas fa-lightbulb me-1"></i>In sintesi</div>';
        if (!deviations.length) {
            html += '<div class="pn-summary-empty">Nessuna differenza di almeno ' + DELTA_THRESHOLD + ' punti tra i gruppi: le risposte sono distribuite in modo simile.</div>';
        } else {
            html += '<ul class="pn-summary-list">';
            deviations.forEach(function (d) {
                html += '<li><button type="button" class="pn-summary-item" data-option="' + d.option + '">'
                    + '<span class="pn-delta ' + (d.delta > 0 ? 'is-up' : 'is-down') + '">' + (d.delta > 0 ? '▲ +' : '▼ −') + fmtNum(Math.abs(d.delta)) + ' pp</span>'
                    + '<span><b>' + esc(labelOf(q.options, d.option, 'Opzione')) + '</b> tra <b>' + esc(d.group) + '</b>: '
                    + fmtPct(d.value) + ' contro ' + fmtPct(d.total) + ' del totale</span>'
                    + '</button></li>';
            });
            html += '</ul>';
        }
        html += '</div>';

        html += optionNavigatorHtml('pnCrossOptNav', 'Risposta', q.options, indexes, option)
            + '<div class="pn-xs-chart" id="pnCrossChoiceChart"></div>'
            + '<div class="pn-legend-note">Barre: quota di chi ha scelto la risposta in ciascun gruppo di <b>' + esc(cross.label) + '</b> · '
            + 'linea tratteggiata = Totale · verde/rosso = almeno ' + DELTA_THRESHOLD + ' punti sopra/sotto · gruppi con base inferiore a ' + LOW_BASE + ' in trasparenza</div>';

        $('pnCrossBody').innerHTML = html;

        $('pnCrossBody').querySelectorAll('.pn-summary-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                crossChoiceState.option = parseInt(btn.getAttribute('data-option'), 10);
                renderCrossChoiceFocus(data);
            });
        });
        bindOptionNavigator('pnCrossOptNav', indexes, option, function (idx) {
            crossChoiceState.option = idx;
            renderCrossChoiceFocus(data);
        });

        var totalPct = Math.round(pct(data.complete.counts[option], data.complete.base) * 10) / 10;
        var groups = cross.columns.filter(function (c) { return c.dist.base > 0; });
        var categories = ['{t|Totale}\n{n|base ' + fmtInt(data.complete.base) + '}'].concat(groups.map(function (c) {
            return c.label + '\n{n|base ' + fmtInt(c.dist.base) + '}';
        }));
        var values = [{ value: totalPct, itemStyle: { color: '#0f172a' } }].concat(groups.map(function (c) {
            var value = Math.round(pct(c.dist.counts[option] || 0, c.dist.base) * 10) / 10;
            var delta = value - totalPct;
            var color = c.dist.base >= LOW_BASE && Math.abs(delta) >= DELTA_THRESHOLD ? (delta > 0 ? '#16a34a' : '#dc2626') : '#3b82f6';
            return { value: value, n: c.dist.counts[option] || 0, itemStyle: { color: color, opacity: c.dist.base < LOW_BASE ? 0.4 : 1 } };
        }));

        var el = $('pnCrossChoiceChart');
        el.style.height = Math.max(200, categories.length * 40 + 40) + 'px';
        if (crossChoiceChart) { crossChoiceChart.dispose(); }
        crossChoiceChart = echarts.init(el);
        crossChoiceChart.setOption({
            animationDuration: 300,
            grid: { left: 8, right: 56, top: 8, bottom: 8, containLabel: true },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (items) {
                    var i = items[0].dataIndex;
                    var label = i === 0 ? 'Totale' : groups[i - 1].label;
                    var base = i === 0 ? data.complete.base : groups[i - 1].dist.base;
                    return '<b>' + esc(label) + '</b> · base ' + fmtInt(base) + '<br>' + esc(labelOf(q.options, option, 'Opzione')) + ': <b>' + fmtNum(items[0].value) + '%</b>';
                }
            },
            xAxis: { type: 'value', max: function (v) { return Math.min(100, Math.ceil((v.max + 5) / 10) * 10); }, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b', formatter: '{value}%' } },
            yAxis: {
                type: 'category', inverse: true, data: categories,
                axisTick: { show: false }, axisLine: { show: false },
                axisLabel: { color: '#334155', fontSize: 12, width: 200, overflow: 'break', lineHeight: 15,
                    rich: { t: { fontWeight: 800, color: '#0f172a', fontSize: 12 }, n: { color: '#94a3b8', fontSize: 10 } } }
            },
            series: [{
                type: 'bar', barWidth: 18, data: values,
                itemStyle: { borderRadius: [0, 4, 4, 0] },
                label: { show: true, position: 'right', color: '#0f172a', fontWeight: 700, formatter: function (p) { return fmtNum(p.value) + '%'; } },
                markLine: { symbol: 'none', silent: true, lineStyle: { color: '#0f172a', type: 'dashed', opacity: 0.45 }, label: { show: false }, data: [{ xAxis: totalPct }] }
            }]
        }, true);
    }

    /* ---------------------------------------------------------------
       Incrocio griglie: grafici invece della tabella a 3 dimensioni.
       - "Per gruppo": un gruppo alla volta, una barra 100% per riga
         della griglia + barra sottile del Totale per confronto
       - "Per riga":   una riga della griglia, tutti i gruppi a confronto
       Griglie con molte colonne (slider): media invece della composizione.
    --------------------------------------------------------------- */
    var MAX_STACK_COLS = 11;
    var crossScaleState = { key: '', mode: 'group', group: 1, row: 0 };
    var crossChart = null;

    function renderCrossScale(data) {
        var q = data.question;
        var rowCount = data.complete.counts.length;
        var stateKey = q.id + '|' + currentCross;
        if (crossScaleState.key !== stateKey) {
            crossScaleState = { key: stateKey, mode: rowCount > 1 ? 'group' : 'row', group: 1, row: firstAnsweredRow(data.complete) };
        }
        var state = crossScaleState;

        var groups = [{ label: 'Totale', key: '', isTotal: true, dist: data.complete }].concat(
            data.cross.columns.map(function (col) { return { label: col.label, key: col.key, dist: col.dist }; })
        );
        state.group = Math.min(Math.max(1, state.group), groups.length - 1);

        var html = '<div class="pn-xs-controls">';
        if (rowCount > 1) {
            html += '<div class="pn-seg" role="group">'
                + '<button type="button" class="pn-seg-btn' + (state.mode === 'group' ? ' is-active' : '') + '" data-xs-mode="group">Un gruppo alla volta</button>'
                + '<button type="button" class="pn-seg-btn' + (state.mode === 'row' ? ' is-active' : '') + '" data-xs-mode="row">Tutti i gruppi per riga</button>'
                + '</div>';
        }
        if (state.mode === 'row' && rowCount > 1) {
            html += '<select class="pn-select" id="pnXsRow">';
            data.complete.counts.forEach(function (_, idx) {
                if ((data.complete.row_bases[idx] || 0) === 0) { return; }
                html += '<option value="' + idx + '"' + (idx === state.row ? ' selected' : '') + '>' + esc(labelOf(q.rows, idx, 'Riga')) + '</option>';
            });
            html += '</select>';
        }
        html += '</div>';

        if (state.mode === 'group') {
            html += '<div class="pn-xs-navlabel"><i class="fas fa-layer-group me-1"></i>Gruppi di <b>' + esc(data.cross.label) + '</b> · scegli quale visualizzare</div>'
                + '<div class="pn-xs-nav">'
                + '<button type="button" class="pn-xs-arrow" data-xs-step="-1" title="Gruppo precedente"' + (state.group <= 1 ? ' disabled' : '') + '><i class="fas fa-chevron-left"></i></button>'
                + '<div class="pn-xs-pills">';
            groups.forEach(function (g, idx) {
                if (g.isTotal) { return; }
                html += '<button type="button" class="pn-xs-pill' + (idx === state.group ? ' is-active' : '') + (g.dist.base < LOW_BASE ? ' is-low' : '') + '" data-xs-group="' + idx + '">'
                    + esc(g.label) + '<span>' + fmtInt(g.dist.base) + '</span></button>';
            });
            html += '</div>'
                + '<button type="button" class="pn-xs-arrow" data-xs-step="1" title="Gruppo successivo"' + (state.group >= groups.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-right"></i></button>'
                + '</div>';
        }

        html += '<div class="pn-xs-chart" id="pnCrossChart"></div><div class="pn-legend-note" id="pnCrossChartNote"></div>';
        $('pnCrossBody').innerHTML = html;
        bindCrossScaleControls(data, groups);

        var numeric = (q.cols || []).length > MAX_STACK_COLS;
        var built = state.mode === 'group'
            ? (numeric ? crossGroupMeanOption(q, groups[0], groups[state.group]) : crossGroupStackOption(q, groups[0], groups[state.group]))
            : (numeric ? crossRowMeanOption(q, groups, state.row) : crossRowStackOption(q, groups, state.row));

        $('pnCrossChartNote').innerHTML = built.note;
        var el = $('pnCrossChart');
        if (!built.option) {
            el.innerHTML = emptyInline('Nessuna risposta per questa selezione');
            el.style.height = 'auto';
            return;
        }
        el.style.height = built.height + 'px';
        if (crossChart) { crossChart.dispose(); }
        crossChart = echarts.init(el);
        crossChart.setOption(built.option, true);
    }

    function firstAnsweredRow(dist) {
        for (var i = 0; i < dist.row_bases.length; i++) {
            if (dist.row_bases[i] > 0) { return i; }
        }
        return 0;
    }

    function bindCrossScaleControls(data, groups) {
        var body = $('pnCrossBody');
        body.querySelectorAll('[data-xs-mode]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                crossScaleState.mode = btn.getAttribute('data-xs-mode');
                renderCrossScale(data);
            });
        });
        body.querySelectorAll('[data-xs-group]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                crossScaleState.group = parseInt(btn.getAttribute('data-xs-group'), 10);
                renderCrossScale(data);
            });
        });
        body.querySelectorAll('[data-xs-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                crossScaleState.group += parseInt(btn.getAttribute('data-xs-step'), 10);
                renderCrossScale(data);
            });
        });
        var rowPicker = $('pnXsRow');
        if (rowPicker) {
            rowPicker.addEventListener('change', function () {
                crossScaleState.row = parseInt(this.value, 10);
                renderCrossScale(data);
            });
        }
        var active = body.querySelector('.pn-xs-pill.is-active');
        if (active && active.scrollIntoView) {
            active.scrollIntoView({ block: 'nearest', inline: 'center' });
        }
    }

    /* Palette ordinale per le colonne della scala: blu -> grigio -> arancio */
    function scaleColors(n) {
        if (n <= 1) { return ['#2563eb']; }
        if (n === 2) { return ['#2563eb', '#cbd5e1']; }
        var stops = [[29, 78, 216], [226, 232, 240], [194, 65, 12]];
        var colors = [];
        for (var i = 0; i < n; i++) {
            var t = i / (n - 1);
            var a = t < 0.5 ? stops[0] : stops[1];
            var b = t < 0.5 ? stops[1] : stops[2];
            var local = t < 0.5 ? t * 2 : (t - 0.5) * 2;
            colors.push('rgb(' + a.map(function (v, k) { return Math.round(v + (b[k] - v) * local); }).join(',') + ')');
        }
        return colors;
    }

    function isDark(color) {
        var v;
        var hex = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(color);
        var rgb = /(\d+),(\d+),(\d+)/.exec(color.replace(/\s/g, ''));
        if (hex) {
            v = hex.slice(1, 4).map(function (x) { return parseInt(x, 16); });
        } else if (rgb) {
            v = rgb.slice(1, 4).map(Number);
        } else {
            return true;
        }
        return (v[0] * 0.299 + v[1] * 0.587 + v[2] * 0.114) < 150;
    }

    function stackSeries(q, name, colIdx, color, values, opts) {
        return Object.assign({
            name: name, type: 'bar', stack: opts.stack,
            barWidth: opts.barWidth,
            itemStyle: { color: color, opacity: opts.opacity || 1, borderColor: '#fff', borderWidth: opts.border === false ? 0 : 1 },
            label: {
                show: !!opts.labels, color: isDark(color) ? '#fff' : '#0f172a', fontSize: 11, fontWeight: 600,
                formatter: function (p) { return p.value >= 8 ? Math.round(p.value) + '%' : ''; }
            },
            data: values
        }, opts.extra || {});
    }

    function stackBaseOption(categories, series, legendNames, tooltipFormatter) {
        return {
            animationDuration: 300,
            grid: { left: 8, right: 16, top: 40, bottom: 8, containLabel: true },
            legend: { data: legendNames || undefined, top: 0, left: 0, type: 'scroll', icon: 'roundRect', itemWidth: 12, itemHeight: 8, textStyle: baseTextStyle() },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: tooltipFormatter },
            xAxis: { type: 'value', max: 100, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b', formatter: '{value}%' } },
            yAxis: {
                type: 'category', inverse: true, data: categories,
                axisTick: { show: false }, axisLine: { show: false },
                axisLabel: { color: '#334155', fontSize: 12, width: 200, overflow: 'break', lineHeight: 15 }
            },
            series: series
        };
    }

    function crossGroupStackOption(q, total, group) {
        var rows = [];
        group.dist.row_bases.forEach(function (base, r) { if (base > 0) { rows.push(r); } });
        if (!rows.length) { return { option: null, note: '' }; }

        var colors = scaleColors(q.cols.length);
        var series = [];
        q.cols.forEach(function (_, c) {
            var name = labelOf(q.cols, c, 'Col.');
            series.push(stackSeries(q, name, c, colors[c], rows.map(function (r) {
                return { value: Math.round(pct(group.dist.counts[r][c], group.dist.row_bases[r]) * 10) / 10, n: group.dist.counts[r][c] };
            }), { stack: 'group', barWidth: 20, labels: true }));
            series.push(stackSeries(q, name, c, colors[c], rows.map(function (r) {
                return { value: Math.round(pct(total.dist.counts[r][c], total.dist.row_bases[r]) * 10) / 10, n: total.dist.counts[r][c] };
            }), { stack: 'total', barWidth: 7, opacity: 0.45, border: false, labels: false }));
        });

        var categories = rows.map(function (r) { return labelOf(q.rows, r, 'Riga'); });
        var option = stackBaseOption(categories, series, q.cols.map(function (_, c) { return labelOf(q.cols, c, 'Col.'); }), function (items) {
            var r = rows[items[0].dataIndex];
            var html = '<b>' + esc(categories[items[0].dataIndex]) + '</b>'
                + '<br><span style="color:#64748b">' + esc(group.label) + ' (base ' + fmtInt(group.dist.row_bases[r]) + ') · Totale (base ' + fmtInt(total.dist.row_bases[r]) + ')</span>';
            for (var i = 0; i < items.length; i += 2) {
                var g = items[i];
                var t = items[i + 1];
                var delta = g.value - (t ? t.value : 0);
                html += '<br>' + g.marker + esc(g.seriesName) + ': <b>' + fmtNum(g.value) + '%</b> vs ' + fmtNum(t ? t.value : 0) + '%'
                    + (Math.abs(delta) >= DELTA_THRESHOLD ? ' <span style="color:' + (delta > 0 ? '#15803d' : '#b91c1c') + '">(' + (delta > 0 ? '+' : '') + fmtNum(delta) + ')</span>' : '');
            }
            return html;
        });
        option.series.forEach(function (s) { s.barGap = '15%'; });

        return {
            option: option,
            height: Math.max(180, rows.length * 46 + 70),
            note: '<b>' + esc(group.label) + '</b> · base ' + fmtInt(group.dist.base)
                + (group.dist.base < LOW_BASE ? ' (bassa)' : '')
                + ' · la barra sottile sotto ogni riga è il <b>Totale</b>, per confronto · righe non viste dal gruppo nascoste'
        };
    }

    function crossRowStackOption(q, groups, row) {
        var visible = groups.filter(function (g) { return (g.dist.row_bases[row] || 0) > 0; });
        if (!visible.length) { return { option: null, note: '' }; }

        var colors = scaleColors(q.cols.length);
        var series = q.cols.map(function (_, c) {
            return stackSeries(q, labelOf(q.cols, c, 'Col.'), c, colors[c], visible.map(function (g) {
                return {
                    value: Math.round(pct(g.dist.counts[row][c], g.dist.row_bases[row]) * 10) / 10,
                    n: g.dist.counts[row][c],
                    itemStyle: { opacity: !g.isTotal && g.dist.row_bases[row] < LOW_BASE ? 0.4 : 1 }
                };
            }), { stack: 'all', barWidth: 22, labels: true });
        });

        var categories = visible.map(function (g) {
            return (g.isTotal ? '{t|Totale}' : g.label) + '\n{n|base ' + fmtInt(g.dist.row_bases[row]) + '}';
        });
        var option = stackBaseOption(categories, series, null, function (items) {
            var g = visible[items[0].dataIndex];
            var html = '<b>' + esc(g.label) + '</b> · base ' + fmtInt(g.dist.row_bases[row]);
            items.forEach(function (it) {
                html += '<br>' + it.marker + esc(it.seriesName) + ': <b>' + fmtNum(it.value) + '%</b> (' + fmtInt(it.data.n) + ')';
            });
            return html;
        });
        option.yAxis.axisLabel.rich = { t: { fontWeight: 800, color: '#0f172a', fontSize: 12 }, n: { color: '#94a3b8', fontSize: 10 } };

        return {
            option: option,
            height: Math.max(180, visible.length * 42 + 70),
            note: 'Riga <b>' + esc(labelOf(q.rows, row, 'Riga')) + '</b> · gruppi con base inferiore a ' + LOW_BASE + ' in trasparenza'
        };
    }

    /* Valore numerico di ogni colonna: l'etichetta se numerica, altrimenti la posizione 1..n */
    function columnValues(q) {
        var allNumeric = q.cols.length > 0 && q.cols.every(function (c) { return c !== '' && !isNaN(Number(String(c).replace(',', '.'))); });
        return q.cols.map(function (c, idx) { return allNumeric ? Number(String(c).replace(',', '.')) : idx + 1; });
    }

    function rowMean(dist, row, values) {
        var base = dist.row_bases[row] || 0;
        if (base === 0) { return null; }
        var sum = 0;
        dist.counts[row].forEach(function (count, c) { sum += count * values[c]; });
        return Math.round((sum / base) * 10) / 10;
    }

    function meanBaseOption(categories, series, tooltipFormatter) {
        return {
            animationDuration: 300,
            grid: { left: 8, right: 40, top: 30, bottom: 8, containLabel: true },
            legend: { top: 0, left: 0, icon: 'roundRect', itemWidth: 12, itemHeight: 8, textStyle: baseTextStyle() },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: tooltipFormatter },
            xAxis: { type: 'value', scale: true, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b' } },
            yAxis: {
                type: 'category', inverse: true, data: categories,
                axisTick: { show: false }, axisLine: { show: false },
                axisLabel: { color: '#334155', fontSize: 12, width: 200, overflow: 'break', lineHeight: 15 }
            },
            series: series
        };
    }

    function crossGroupMeanOption(q, total, group) {
        var values = columnValues(q);
        var rows = [];
        group.dist.row_bases.forEach(function (base, r) { if (base > 0) { rows.push(r); } });
        if (!rows.length) { return { option: null, note: '' }; }

        var categories = rows.map(function (r) { return labelOf(q.rows, r, 'Riga'); });
        var option = meanBaseOption(categories, [
            { name: group.label, type: 'bar', barWidth: 18, itemStyle: { color: '#2563eb', borderRadius: [0, 4, 4, 0] },
              label: { show: true, position: 'right', fontWeight: 700, color: '#0f172a', formatter: function (p) { return fmtNum(p.value); } },
              data: rows.map(function (r) { return rowMean(group.dist, r, values); }) },
            { name: 'Totale', type: 'bar', barWidth: 7, itemStyle: { color: '#94a3b8', borderRadius: [0, 3, 3, 0] },
              data: rows.map(function (r) { return rowMean(total.dist, r, values); }) }
        ], function (items) {
            var html = '<b>' + esc(categories[items[0].dataIndex]) + '</b>';
            items.forEach(function (it) { html += '<br>' + it.marker + esc(it.seriesName) + ': <b>' + fmtNum(it.value) + '</b>'; });
            return html;
        });

        return {
            option: option,
            height: Math.max(160, rows.length * 44 + 60),
            note: 'Scala con ' + q.cols.length + ' punti: mostrata la <b>media</b> · <b>' + esc(group.label) + '</b> base ' + fmtInt(group.dist.base)
        };
    }

    function crossRowMeanOption(q, groups, row) {
        var values = columnValues(q);
        var visible = groups.filter(function (g) { return (g.dist.row_bases[row] || 0) > 0; });
        if (!visible.length) { return { option: null, note: '' }; }

        var categories = visible.map(function (g) {
            return (g.isTotal ? '{t|Totale}' : g.label) + '\n{n|base ' + fmtInt(g.dist.row_bases[row]) + '}';
        });
        var option = meanBaseOption(categories, [{
            name: 'Media', type: 'bar', barWidth: 18,
            label: { show: true, position: 'right', fontWeight: 700, color: '#0f172a', formatter: function (p) { return fmtNum(p.value); } },
            data: visible.map(function (g) {
                return {
                    value: rowMean(g.dist, row, values),
                    itemStyle: {
                        color: g.isTotal ? '#0f172a' : '#2563eb', borderRadius: [0, 4, 4, 0],
                        opacity: !g.isTotal && g.dist.row_bases[row] < LOW_BASE ? 0.4 : 1
                    }
                };
            })
        }], function (items) {
            var g = visible[items[0].dataIndex];
            return '<b>' + esc(g.label) + '</b> · base ' + fmtInt(g.dist.row_bases[row]) + '<br>Media: <b>' + fmtNum(items[0].value) + '</b>';
        });
        option.legend.show = false;
        option.yAxis.axisLabel.rich = { t: { fontWeight: 800, color: '#0f172a', fontSize: 12 }, n: { color: '#94a3b8', fontSize: 10 } };

        return {
            option: option,
            height: Math.max(160, visible.length * 40 + 50),
            note: 'Scala con ' + q.cols.length + ' punti: mostrata la <b>media</b> per gruppo · gruppi con base inferiore a ' + LOW_BASE + ' in trasparenza'
        };
    }

    function groupHeader(col, cross) {
        return '<b>' + esc(col.label) + '</b>' + (cross.show_keys ? ' <span class="pn-xtab-key">(' + esc(cross.show_keys + ' ' + col.key) + ')</span>' : '');
    }

    /**
     * Tabella comparativa: gruppi in riga (Totale in testa), risposte in colonna.
     * Per ogni risposta: % e frequenza del gruppo + scarto in punti percentuali
     * dal Totale (evidenziato da DELTA_THRESHOLD in su, solo con base >= LOW_BASE).
     * Molte risposte = tabella larga con scorrimento interno, nessuna colonna nascosta.
     */
    function crossTable(answerLabels, totalCounts, totalBase, cross, pick, isMultiple) {
        var groups = [{ label: 'Totale', key: '', isTotal: true, counts: totalCounts, base: totalBase }];
        cross.columns.forEach(function (col) {
            var picked = pick(col.dist);
            groups.push({ label: col.label, key: col.key, header: groupHeader(col, cross), counts: picked.counts || [], base: picked.base });
        });

        var answers = totalCounts.map(function (count, idx) {
            return { idx: idx, label: labelOf(answerLabels, idx, 'Opzione'), totalPct: pct(count, totalBase) };
        });

        var html = '<div class="table-responsive pn-xtab-wrap"><table class="table table-sm pn-xtab mb-0"><thead><tr>'
            + '<th class="pn-xtab-rowhead">Gruppo</th>';
        answers.forEach(function (a) {
            html += '<th class="pn-xtab-num" colspan="2">' + esc(a.label) + '</th>';
        });
        html += '<th class="pn-xtab-num pn-xtab-basecol">Base</th></tr></thead><tbody>';

        groups.forEach(function (g) {
            html += '<tr class="' + (g.isTotal ? 'is-total' : '') + '"><td class="pn-xtab-rowhead">' + (g.header || '<b>' + esc(g.label) + '</b>') + '</td>';
            answers.forEach(function (a) { html += crossCells(g, a); });
            html += '<td class="pn-xtab-num pn-xtab-basecol">' + fmtInt(g.base) + (isLowBase(g) ? ' *' : '') + '</td></tr>';
        });

        return html + '</tbody></table></div>' + crossLegend(isMultiple);
    }

    function isLowBase(group) {
        return !group.isTotal && group.base > 0 && group.base < LOW_BASE;
    }

    function crossCells(group, answer) {
        var count = group.counts[answer.idx] || 0;
        var value = pct(count, group.base);
        var valueCell = '<td class="pn-xtab-num pn-xtab-val">'
            + '<span class="pn-cell-pct">' + (group.base > 0 ? fmtPct(value) : '–') + '</span>'
            + ' <span class="pn-cell-n">(' + fmtInt(count) + ')</span></td>';

        if (group.isTotal || group.base < LOW_BASE) {
            return valueCell + '<td class="pn-xtab-delta"></td>';
        }

        var delta = value - answer.totalPct;
        var rounded = Math.round(delta * 10) / 10;
        var cls = Math.abs(delta) >= DELTA_THRESHOLD ? (delta > 0 ? 'is-up' : 'is-down') : 'is-flat';
        var arrow = rounded > 0 ? '▲ +' : (rounded < 0 ? '▼ −' : '');
        return valueCell + '<td class="pn-xtab-delta"><span class="pn-delta ' + cls + '">'
            + arrow + fmtNum(Math.abs(rounded)) + ' pp</span></td>';
    }

    function crossLegend(isMultiple) {
        return '<div class="pn-legend-note"><i class="fas fa-circle-info me-1"></i>'
            + '<b>pp</b> = differenza in punti percentuali rispetto al Totale; evidenziata in '
            + '<span class="pn-delta is-up">verde</span> / <span class="pn-delta is-down">rosso</span> da ' + DELTA_THRESHOLD + ' pp in su. '
            + 'È una differenza descrittiva, non indica di per sé significatività statistica.'
            + ' · * base inferiore a ' + LOW_BASE + ' (scarto non calcolato)'
            + (isMultiple ? ' · risposta multipla: la somma per riga può superare il 100%' : '')
            + '</div>';
    }

    function crossOpenTable(data) {
        var cross = data.cross;
        var groups = [{ label: 'Totale', isTotal: true, stats: data.complete.stats }];
        cross.columns.forEach(function (col) {
            groups.push({ label: col.label, header: groupHeader(col, cross), stats: col.dist.stats });
        });

        var metrics = [
            ['Risposte', function (s) { return fmtInt(s.count); }],
            ['Media', function (s) { return fmtNum(s.mean); }],
            ['Mediana', function (s) { return fmtNum(s.median); }],
            ['Minimo', function (s) { return fmtNum(s.min); }],
            ['Massimo', function (s) { return fmtNum(s.max); }]
        ];
        // Gruppi in riga, statistiche in colonna (stessa lettura della tabella a scelta)
        var html = '<div class="table-responsive pn-xtab-wrap"><table class="table table-sm pn-xtab mb-0"><thead><tr><th class="pn-xtab-rowhead">Gruppo</th>';
        metrics.forEach(function (m) { html += '<th class="pn-xtab-num">' + m[0] + '</th>'; });
        html += '</tr></thead><tbody>';
        groups.forEach(function (g) {
            html += '<tr class="' + (g.isTotal ? 'is-total' : '') + '"><td class="pn-xtab-rowhead">' + (g.header || '<b>' + esc(g.label) + '</b>') + '</td>';
            metrics.forEach(function (m) {
                html += '<td class="pn-xtab-num"><span class="pn-cell-pct">' + (g.stats ? m[1](g.stats) : '–') + '</span></td>';
            });
            html += '</tr>';
        });
        return html + '</tbody></table></div>';
    }

    /* ---------------------------------------------------------------
       Risposte giornaliere (ECharts)
       - choice singola: colonne 100% in pila (composizione per giorno)
       - choice multipla: heatmap risposta x giorno
       - aperta numerica: media per giorno
       Nascosto per griglie e quando c'e' una sola risposta possibile.
    --------------------------------------------------------------- */
    function cumulate(values) {
        var sum = 0;
        return values.map(function (v) { sum += v; return sum; });
    }

    function fmtDay(day) {
        var p = day.split('-');
        return p[2] + '/' + p[1];
    }

    function renderTrend(data) {
        var card = $('pnTrendCard');
        var q = data.question;
        var days = Object.keys(data.daily || {}).filter(function (d) { return data.daily[d].answered > 0; });
        var activeOptions = q.type === 'choice'
            ? data.complete.counts.map(function (c, idx) { return c > 0 ? idx : -1; }).filter(function (idx) { return idx >= 0; })
            : [];

        var supported = days.length > 0 && (
            (q.type === 'choice' && activeOptions.length > 1) ||
            (q.type === 'open' && data.complete.stats)
        );

        if (!supported || typeof echarts === 'undefined') {
            card.classList.add('d-none');
            return;
        }
        card.classList.remove('d-none');

        // Card chiusa: solo il titolo, il grafico si disegna all'apertura
        if (!trendOpen) {
            $('pnTrendTitle').textContent = q.type === 'open'
                ? 'Media giornaliera delle risposte numeriche'
                : 'Composizione giornaliera delle risposte';
            return;
        }

        var cumulative = trendMode === 'cumulative';
        var answered = days.map(function (d) { return data.daily[d].answered; });
        var bases = cumulative ? cumulate(answered) : answered;
        var axisLabels = days.map(function (d, i) { return fmtDay(d) + '\nn=' + fmtInt(bases[i]); });

        var option;
        var height = 340;
        $('pnTrendControls').innerHTML = '';
        if (q.type === 'open') {
            option = trendOpenOption(data, days, axisLabels, bases, cumulative);
        } else if (activeOptions.length > MANY_OPTIONS) {
            option = trendFocusOption(data, days, axisLabels, bases, activeOptions, cumulative);
            height = 300;
        } else if (data.is_multiple) {
            option = trendHeatmapOption(data, days, axisLabels, bases, activeOptions, cumulative);
            height = Math.max(260, activeOptions.length * 34 + 110);
        } else {
            option = trendStackedOption(data, days, axisLabels, bases, activeOptions, cumulative);
        }

        var note = cumulative ? 'Valori cumulati dall\'inizio del field' : 'Valori del singolo giorno';
        if (!cumulative && answered.some(function (n) { return n < LOW_DAY_BASE; })) {
            note += ' · giorni con meno di ' + LOW_DAY_BASE + ' risposte in trasparenza';
        }
        $('pnTrendNote').textContent = note;

        var el = $('pnChartTrend');
        el.style.height = height + 'px';
        if (!trendChart) { trendChart = echarts.init(el); }
        trendChart.resize();
        trendChart.setOption(option, true);
    }

    function baseTextStyle() {
        return { color: '#475569', fontSize: 12 };
    }

    function categoryAxis(labels) {
        return {
            type: 'category', data: labels,
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#cbd5e1' } },
            axisLabel: { color: '#64748b', fontSize: 11, lineHeight: 15 }
        };
    }

    function dayOpacity(base, cumulative) {
        return !cumulative && base < LOW_DAY_BASE ? 0.35 : 1;
    }

    function trendStackedOption(data, days, axisLabels, bases, activeOptions, cumulative) {
        var q = data.question;
        $('pnTrendTitle').textContent = 'Composizione giornaliera delle risposte';

        var series = activeOptions.map(function (idx, pos) {
            var counts = days.map(function (d) { return data.daily[d].options[idx] || 0; });
            var values = cumulative ? cumulate(counts) : counts;
            return {
                name: labelOf(q.options, idx, 'Opzione'),
                type: 'bar', stack: 'total', barMaxWidth: 46,
                itemStyle: { color: SERIES_COLORS[pos % SERIES_COLORS.length], borderColor: '#fff', borderWidth: 1 },
                label: {
                    show: true, color: '#fff', fontSize: 11, fontWeight: 600,
                    formatter: function (p) { return p.value >= 8 ? Math.round(p.value) + '%' : ''; }
                },
                emphasis: { focus: 'series' },
                data: values.map(function (v, i) {
                    return {
                        value: bases[i] > 0 ? Math.round((v / bases[i]) * 1000) / 10 : 0,
                        n: v,
                        itemStyle: { opacity: dayOpacity(bases[i], cumulative) }
                    };
                })
            };
        });

        return {
            animationDuration: 350,
            grid: { left: 46, right: 12, top: 52, bottom: 44 },
            legend: {
                type: 'scroll', top: 0, left: 0, right: 0, icon: 'roundRect', itemWidth: 12, itemHeight: 8, textStyle: baseTextStyle(),
                formatter: function (name) { return name.length > 34 ? name.slice(0, 33) + '…' : name; },
                tooltip: { show: true }
            },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (items) {
                    var i = items[0].dataIndex;
                    var html = '<b>' + fmtDay(days[i]) + '</b> · base ' + fmtInt(bases[i]);
                    items.slice().reverse().forEach(function (it) {
                        html += '<br>' + it.marker + esc(it.seriesName) + ': <b>' + fmtNum(it.value) + '%</b> (' + fmtInt(it.data.n) + ')';
                    });
                    return html;
                }
            },
            xAxis: categoryAxis(axisLabels),
            yAxis: { type: 'value', max: 100, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b', formatter: '{value}%' } },
            series: series
        };
    }

    function trendHeatmapOption(data, days, axisLabels, bases, activeOptions, cumulative) {
        var q = data.question;
        $('pnTrendTitle').textContent = 'Risposte giornaliere (% di chi ha risposto, multipla)';

        // Ordine: risposta piu' frequente in alto
        var ordered = activeOptions.slice().sort(function (a, b) { return data.complete.counts[a] - data.complete.counts[b]; });
        var cells = [];
        ordered.forEach(function (idx, y) {
            var counts = days.map(function (d) { return data.daily[d].options[idx] || 0; });
            var values = cumulative ? cumulate(counts) : counts;
            values.forEach(function (v, x) {
                var value = bases[x] > 0 ? Math.round((v / bases[x]) * 1000) / 10 : 0;
                cells.push({
                    value: [x, y, value, v],
                    itemStyle: { opacity: dayOpacity(bases[x], cumulative) },
                    label: { color: value >= 50 ? '#fff' : '#0f172a' }
                });
            });
        });

        return {
            animationDuration: 350,
            grid: { left: 8, right: 12, top: 8, bottom: 74, containLabel: true },
            tooltip: {
                formatter: function (p) {
                    return '<b>' + fmtDay(days[p.value[0]]) + '</b> · base ' + fmtInt(bases[p.value[0]])
                        + '<br>' + esc(labelOf(q.options, ordered[p.value[1]], 'Opzione')) + ': <b>' + fmtNum(p.value[2]) + '%</b> (' + fmtInt(p.value[3]) + ')';
                }
            },
            xAxis: Object.assign(categoryAxis(axisLabels), { position: 'top', splitArea: { show: false } }),
            yAxis: {
                type: 'category',
                data: ordered.map(function (idx) { return labelOf(q.options, idx, 'Opzione'); }),
                axisTick: { show: false }, axisLine: { show: false },
                axisLabel: { color: '#334155', fontSize: 12, width: 220, overflow: 'break' }
            },
            visualMap: {
                min: 0, max: 100, calculable: false, orient: 'horizontal', left: 'center', bottom: 4,
                itemWidth: 12, itemHeight: 160, text: ['100%', '0%'], textStyle: { color: '#64748b', fontSize: 11 },
                inRange: { color: ['#eff6ff', '#93c5fd', '#2563eb', '#1e3a8a'] }
            },
            series: [{
                type: 'heatmap', data: cells,
                itemStyle: { borderColor: '#fff', borderWidth: 2, borderRadius: 4 },
                label: {
                    show: true, fontSize: 11, fontWeight: 600,
                    formatter: function (p) { return Math.round(p.value[2]) + '%'; }
                },
                emphasis: { itemStyle: { shadowBlur: 6, shadowColor: 'rgba(15,23,42,.25)' } }
            }]
        };
    }

    /* Molte risposte: una alla volta, % giornaliera contro la % complessiva */
    var trendOptionState = { key: '', option: 0 };

    function trendFocusOption(data, days, axisLabels, bases, activeOptions, cumulative) {
        var q = data.question;
        if (trendOptionState.key !== String(q.id) || activeOptions.indexOf(trendOptionState.option) === -1) {
            trendOptionState = { key: String(q.id), option: mostFrequentOption(data) };
        }
        var option = trendOptionState.option;
        var label = labelOf(q.options, option, 'Opzione');
        $('pnTrendTitle').textContent = 'Andamento giornaliero di una risposta';

        var counts = days.map(function (d) { return data.daily[d].options[option] || 0; });
        var values = cumulative ? cumulate(counts) : counts;
        var series = values.map(function (v, i) { return bases[i] > 0 ? Math.round((v / bases[i]) * 1000) / 10 : null; });
        var overall = Math.round(pct(data.complete.counts[option], data.complete.base) * 10) / 10;

        // Sintesi su giorni con base sufficiente
        var reliable = [];
        series.forEach(function (v, i) { if (v !== null && (cumulative || bases[i] >= LOW_DAY_BASE)) { reliable.push(i); } });
        var summary = 'Complessivamente <b>' + fmtPct(overall) + '</b>';
        if (reliable.length > 1) {
            var min = reliable[0];
            var max = reliable[0];
            reliable.forEach(function (i) {
                if (series[i] < series[min]) { min = i; }
                if (series[i] > series[max]) { max = i; }
            });
            summary += ' · minimo <b>' + fmtPct(series[min]) + '</b> il ' + fmtDay(days[min])
                + ' · massimo <b>' + fmtPct(series[max]) + '</b> il ' + fmtDay(days[max]);
        }

        $('pnTrendControls').innerHTML = optionNavigatorHtml('pnTrendOptNav', 'Risposta', q.options, activeOptions, option)
            + '<div class="pn-summary pn-summary--inline"><i class="fas fa-lightbulb me-1"></i>' + summary + '</div>';
        bindOptionNavigator('pnTrendOptNav', activeOptions, option, function (idx) {
            trendOptionState.option = idx;
            renderTrend(lastData);
        });

        return {
            animationDuration: 300,
            grid: { left: 46, right: 16, top: 24, bottom: 44 },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (items) {
                    var i = items[0].dataIndex;
                    return '<b>' + fmtDay(days[i]) + '</b> · base ' + fmtInt(bases[i]) + '<br>' + esc(label) + ': <b>'
                        + (items[0].value == null ? '–' : fmtNum(items[0].value) + '%') + '</b> (' + fmtInt(values[i]) + ')';
                }
            },
            xAxis: categoryAxis(axisLabels),
            yAxis: { type: 'value', max: function (v) { return Math.min(100, Math.ceil((v.max + 5) / 10) * 10); }, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b', formatter: '{value}%' } },
            series: [{
                type: 'bar', barMaxWidth: 46,
                itemStyle: { color: '#2563eb', borderRadius: [5, 5, 0, 0] },
                label: { show: true, position: 'top', color: '#0f172a', fontWeight: 700, fontSize: 11, formatter: function (p) { return p.value == null ? '' : fmtNum(p.value) + '%'; } },
                markLine: {
                    symbol: 'none', silent: true,
                    lineStyle: { color: '#94a3b8', type: 'dashed' },
                    label: { formatter: 'totale ' + fmtNum(overall) + '%', color: '#64748b', fontSize: 11 },
                    data: [{ yAxis: overall }]
                },
                data: series.map(function (v, i) { return { value: v, itemStyle: { opacity: dayOpacity(bases[i], cumulative) } }; })
            }]
        };
    }

    function trendOpenOption(data, days, axisLabels, bases, cumulative) {
        $('pnTrendTitle').textContent = 'Media giornaliera delle risposte numeriche';
        var sums = days.map(function (d) { return data.daily[d].num_sum; });
        var ns = days.map(function (d) { return data.daily[d].num_n; });
        if (cumulative) { sums = cumulate(sums); ns = cumulate(ns); }
        var overall = data.complete.stats.mean;

        return {
            animationDuration: 350,
            grid: { left: 46, right: 12, top: 24, bottom: 44 },
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (items) {
                    var i = items[0].dataIndex;
                    return '<b>' + fmtDay(days[i]) + '</b> · base ' + fmtInt(ns[i]) + '<br>Media: <b>' + fmtNum(items[0].value) + '</b>';
                }
            },
            xAxis: categoryAxis(axisLabels),
            yAxis: { type: 'value', scale: true, splitLine: { lineStyle: { color: '#eef2f6' } }, axisLabel: { color: '#64748b' } },
            series: [{
                type: 'bar', barMaxWidth: 46,
                itemStyle: { color: '#2563eb', borderRadius: [5, 5, 0, 0] },
                label: { show: true, position: 'top', color: '#0f172a', fontWeight: 700, fontSize: 11, formatter: function (p) { return fmtNum(p.value); } },
                markLine: {
                    symbol: 'none', silent: true,
                    lineStyle: { color: '#94a3b8', type: 'dashed' },
                    label: { formatter: 'media totale ' + fmtNum(overall), color: '#64748b', fontSize: 11 },
                    data: [{ yAxis: overall }]
                },
                data: sums.map(function (s, i) {
                    return { value: ns[i] > 0 ? Math.round((s / ns[i]) * 10) / 10 : null, itemStyle: { opacity: dayOpacity(ns[i], cumulative) } };
                })
            }]
        };
    }

    var trendOpen = false;

    function initTrendMode() {
        $('pnTrendToggle').addEventListener('click', function () {
            trendOpen = !trendOpen;
            $('pnTrendCard').classList.toggle('is-open', trendOpen);
            this.setAttribute('aria-expanded', trendOpen ? 'true' : 'false');
            if (lastData) { renderTrend(lastData); }
        });

        document.querySelectorAll('#pnTrendMode .pn-seg-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#pnTrendMode .pn-seg-btn').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                trendMode = btn.getAttribute('data-mode');
                if (lastData) { renderTrend(lastData); }
            });
        });

        window.addEventListener('resize', function () {
            if (trendChart) { trendChart.resize(); }
            if (crossChart) { crossChart.resize(); }
            if (crossChoiceChart) { crossChoiceChart.resize(); }
        });
    }

    /* ---------------------------------------------------------------
       Init
    --------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        if (!$('pnQuestionList')) { return; }
        initSidebar();
        initCross();
        initTrendMode();

        var hash = window.location.hash || '';
        var match = /#q=(\d+)/.exec(hash);
        var crossMatch = /[&#]x=([^&]+)/.exec(hash);
        if (crossMatch) {
            var crossValue = decodeURIComponent(crossMatch[1]);
            var known = Array.prototype.some.call($('pnCrossSelect').options, function (opt) { return opt.value === crossValue; });
            if (known) { currentCross = crossValue; }
        }
        if (match && document.querySelector('.pn-qitem[data-question-id="' + match[1] + '"]')) {
            selectQuestion(parseInt(match[1], 10));
        }
    });
})();
