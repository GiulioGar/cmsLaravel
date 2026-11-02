(function () {
    'use strict';

    const config = window.FieldControlConfig || {};
    const csrfToken = config.csrfToken || '';
    const prj = config.prj || '';
    const sid = config.sid || '';
    const routes = config.routes || {};
    const chartsData = config.filtrateCountsByPanel || {};
    const dailySeriesData = config.dailySeriesByPanel || {};

    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function initProgressBars() {
        document.querySelectorAll('.kpi-progressfill').forEach(function (bar) {
            const pct = parseFloat(bar.getAttribute('data-pct') || '0');
            const safePct = isNaN(pct) ? 0 : Math.max(0, Math.min(100, pct));

            setTimeout(function () {
                bar.style.width = safePct + '%';
            }, 80);
        });

        document.querySelectorAll('.fc-kpi-progress').forEach(function (wrap) {
            const fill = wrap.querySelector('.fc-kpi-progress-fill');
            if (!fill) {
                return;
            }

            let ir = parseFloat(wrap.getAttribute('data-ir') || '0');
            if (isNaN(ir)) {
                ir = 0;
            }

            ir = Math.max(0, Math.min(100, ir));
            fill.style.width = '0%';

            requestAnimationFrame(function () {
                fill.style.width = ir + '%';
            });
        });
    }

    function buildPanelSlug(panelName) {
        return String(panelName)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initCharts() {
        if (typeof echarts === 'undefined') {
            return;
        }

        const maxVisibleFiltrateRows = 6;
        const minPercentageForDenseLabels = 1;
        const denseLabelsThreshold = 6;
        const rowHeight = 34;
        const chartVerticalPadding = 20;

        Object.keys(chartsData).forEach(function (panelName) {
            const panelRows = chartsData[panelName];
            const labels = [];
            const values = [];
            const questionTooltips = [];
            const filteredEntries = [];
            let totalFiltrate = 0;

            if (!panelRows) {
                return;
            }

            Object.entries(panelRows).forEach(function (entry) {
                const question = entry[0];
                const count = Number(entry[1] || 0);

                const parts = question.split(' - ');
                const questionCode = parts[0] || 'N/A';
                const questionText = parts.slice(1).join(' - ') || 'Testo non disponibile';

                labels.push(questionCode);
                values.push(count);
                questionTooltips.push({
                    code: questionCode,
                    text: questionText
                });

                totalFiltrate += count;
            });

            const percentages = values.map(function (value) {
                if (totalFiltrate <= 0) {
                    return 0;
                }

                return (value / totalFiltrate) * 100;
            });
            const minPercentageForVisibleRows = 1;

            labels.forEach(function (label, index) {
                if (percentages[index] > minPercentageForVisibleRows) {
                    filteredEntries.push({
                        label: label,
                        value: values[index],
                        percentage: percentages[index],
                        questionTooltip: questionTooltips[index]
                    });
                }
            });

            filteredEntries.sort(function (left, right) {
                if (right.percentage !== left.percentage) {
                    return right.percentage - left.percentage;
                }

                return right.value - left.value;
            });

            if (!filteredEntries.length) {
                return;
            }

            const visibleLabels = filteredEntries.map(function (entry) {
                return entry.label;
            });
            const visibleValues = filteredEntries.map(function (entry) {
                return entry.value;
            });
            const visiblePercentages = filteredEntries.map(function (entry) {
                return entry.percentage;
            });
            const visibleQuestionTooltips = filteredEntries.map(function (entry) {
                return entry.questionTooltip;
            });
            const hasDenseBars = visibleLabels.length > denseLabelsThreshold;

            const canvasId = 'chart-panel-' + buildPanelSlug(panelName);
            const chartElement = document.getElementById(canvasId);

            if (!chartElement) {
                return;
            }

            const canvasWrapper = chartElement.parentElement;
            const visibleRows = Math.min(visibleLabels.length || 1, maxVisibleFiltrateRows);
            const canvasHeight = Math.max(220, (visibleRows * rowHeight) + chartVerticalPadding);
            const scrollHeight = (visibleLabels.length * rowHeight) + chartVerticalPadding;

            if (canvasWrapper) {
                canvasWrapper.style.height = canvasHeight + 'px';
                canvasWrapper.style.overflowY = visibleLabels.length > maxVisibleFiltrateRows ? 'auto' : 'hidden';
            }

            chartElement.style.height = scrollHeight + 'px';

            function wrapText(text, maxLineLength, maxLines) {
                if (!text) {
                    return [];
                }

                var words = String(text).split(/\s+/);
                var wrapped = [];
                var currentLine = '';

                words.forEach(function (word) {
                    var testLine = currentLine ? (currentLine + ' ' + word) : word;

                    if (testLine.length <= maxLineLength) {
                        currentLine = testLine;
                        return;
                    }

                    if (currentLine) {
                        wrapped.push(currentLine);
                    }

                    currentLine = word;
                });

                if (currentLine) {
                    wrapped.push(currentLine);
                }

                if (wrapped.length > maxLines) {
                    var limited = wrapped.slice(0, maxLines);
                    limited[maxLines - 1] = limited[maxLines - 1].replace(/\s*\.*$/, '') + '...';
                    return limited;
                }

                return wrapped;
            }

            function formatTooltipMetric(label, value, extraClass) {
                var metricClass = extraClass ? (' fc-echart-tooltip-metric ' + extraClass) : ' fc-echart-tooltip-metric';

                return '<div class="' + metricClass + '">' +
                    '<span class="fc-echart-tooltip-metric-label">' + escapeHtml(label) + '</span>' +
                    '<span class="fc-echart-tooltip-metric-value">' + escapeHtml(value) + '</span>' +
                    '</div>';
            }

            var chart = echarts.getInstanceByDom(chartElement) || echarts.init(chartElement, null, {
                renderer: 'canvas'
            });

            chart.setOption({
                animationDuration: 500,
                grid: {
                    top: 6,
                    right: hasDenseBars ? 54 : 40,
                    bottom: 6,
                    left: 52,
                    containLabel: false
                },
                legend: {
                    show: false
                },
                tooltip: {
                    trigger: 'axis',
                    confine: false,
                    appendToBody: true,
                    renderMode: 'html',
                    className: 'fc-echart-tooltip',
                    backgroundColor: 'rgba(15, 23, 42, 0.96)',
                    borderColor: 'rgba(148, 163, 184, 0.28)',
                    borderWidth: 1,
                    padding: 0,
                    textStyle: {
                        color: '#f8fafc',
                        fontSize: 12,
                        lineHeight: 18
                    },
                    axisPointer: {
                        type: 'shadow'
                    },
                    formatter: function (params) {
                        var item = params && params[0] ? params[0] : null;
                        var questionData;
                        var questionCode = 'N/A';
                        var questionText = 'Testo non disponibile';
                        var wrappedText = [];
                        var percentageValue = '0.0%';

                        if (!item) {
                            return '';
                        }

                        questionData = visibleQuestionTooltips[item.dataIndex] || null;
                        percentageValue = visiblePercentages[item.dataIndex].toFixed(1) + '%';

                        if (questionData) {
                            questionCode = questionData.code;
                            questionText = questionData.text;
                        }

                        wrappedText = wrapText(questionText, 44, 5);

                        return '' +
                            '<div class="fc-echart-tooltip-card">' +
                                '<div class="fc-echart-tooltip-head">' +
                                    '<div class="fc-echart-tooltip-kicker">Domanda di screenout</div>' +
                                    '<div class="fc-echart-tooltip-code">' + escapeHtml(questionCode) + '</div>' +
                                '</div>' +
                                '<div class="fc-echart-tooltip-body">' +
                                    '<div class="fc-echart-tooltip-question">' + wrappedText.map(function (line) {
                                        return '<div class="fc-echart-tooltip-question-line">' + escapeHtml(line) + '</div>';
                                    }).join('') + '</div>' +
                                    '<div class="fc-echart-tooltip-divider"></div>' +
                                    '<div class="fc-echart-tooltip-metrics">' +
                                        formatTooltipMetric('Filtrate', String(item.value), 'is-count') +
                                        formatTooltipMetric('% sul totale', percentageValue, 'is-percentage') +
                                    '</div>' +
                                '</div>' +
                            '</div>';
                    }
                },
                xAxis: {
                    type: 'value',
                    minInterval: 1,
                    show: false
                },
                yAxis: {
                    type: 'category',
                    inverse: true,
                    data: visibleLabels,
                    axisTick: {
                        show: false
                    },
                    axisLine: {
                        show: false
                    },
                    axisLabel: {
                        color: '#334155',
                        fontSize: 11,
                        fontWeight: 600
                    },
                    splitLine: {
                        show: true,
                        interval: 0,
                        lineStyle: {
                            color: 'rgba(148, 163, 184, 0.3)',
                            width: 1,
                            type: 'solid',
                            shadowBlur: 6,
                            shadowColor: 'rgba(148, 163, 184, 0.45)'
                        }
                    }
                },
                series: [{
                    name: 'Filtrate',
                    type: 'bar',
                    data: visibleValues,
                    barMaxWidth: 12,
                    label: {
                        show: true,
                        position: 'right',
                        color: '#1f2937',
                        fontSize: 10,
                        fontWeight: 700,
                        formatter: function (params) {
                            var value = Number(params.value || 0);
                            var percentage = Number(visiblePercentages[params.dataIndex] || 0);

                            if (value <= 0) {
                                return '';
                            }

                            if (hasDenseBars && percentage <= minPercentageForDenseLabels) {
                                return '';
                            }

                            return percentage.toFixed(1) + '%';
                        }
                    },
                    itemStyle: {
                        color: '#93c5fd',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 10,
                        shadowBlur: 10,
                        shadowColor: 'rgba(37, 99, 235, 0.28)',
                        shadowOffsetY: 4
                    }
                }]
            });

            chart.resize();
        });
    }

    function formatShortDate(isoDate) {
        var parts = String(isoDate).split('-');

        if (parts.length !== 3) {
            return isoDate;
        }

        return parts[2] + '/' + parts[1];
    }

    function initDailyCharts() {
        if (typeof echarts === 'undefined') {
            return;
        }

        Object.keys(dailySeriesData).forEach(function (seriesName) {
            const rows = dailySeriesData[seriesName] || [];

            if (!rows.length) {
                return;
            }

            const canvasId = 'chart-daily-' + buildPanelSlug(seriesName);
            const chartElement = document.getElementById(canvasId);

            if (!chartElement) {
                return;
            }

            const dates = rows.map(function (row) { return formatShortDate(row.date); });
            const contatti = rows.map(function (row) { return row.contatti; });
            const complete = rows.map(function (row) { return row.complete; });
            const ir = rows.map(function (row) { return row.ir; });

            var chart = echarts.getInstanceByDom(chartElement) || echarts.init(chartElement, null, {
                renderer: 'canvas'
            });

            chart.setOption({
                animationDuration: 500,
                grid: {
                    top: 36,
                    right: 46,
                    bottom: 30,
                    left: 44
                },
                legend: {
                    top: 0,
                    left: 0,
                    textStyle: { color: '#374151', fontSize: 11 },
                    data: ['Contatti', 'Complete', 'IR']
                },
                tooltip: {
                    trigger: 'axis'
                },
                xAxis: {
                    type: 'category',
                    data: dates,
                    axisLabel: { color: '#4b5563', fontSize: 10 },
                    axisLine: { lineStyle: { color: '#d7dee7' } }
                },
                yAxis: [
                    {
                        type: 'value',
                        name: '',
                        axisLabel: { color: '#4b5563', fontSize: 10 },
                        splitLine: { lineStyle: { color: '#edf2f7' } }
                    },
                    {
                        type: 'value',
                        min: 0,
                        max: 100,
                        axisLabel: { color: '#4b5563', fontSize: 10, formatter: '{value}%' },
                        splitLine: { show: false }
                    }
                ],
                series: [
                    {
                        name: 'Contatti',
                        type: 'bar',
                        data: contatti,
                        barMaxWidth: 18,
                        itemStyle: { color: 'rgba(148, 163, 184, 0.55)', borderRadius: [3, 3, 0, 0] }
                    },
                    {
                        name: 'Complete',
                        type: 'bar',
                        data: complete,
                        barMaxWidth: 18,
                        itemStyle: { color: '#16a34a', borderRadius: [3, 3, 0, 0] }
                    },
                    {
                        name: 'IR',
                        type: 'line',
                        yAxisIndex: 1,
                        data: ir,
                        smooth: true,
                        symbolSize: 6,
                        itemStyle: { color: '#f59e0b' },
                        lineStyle: { color: '#f59e0b', width: 2 }
                    }
                ]
            });

            chart.resize();
        });
    }

    function resizeVisibleCharts() {
        if (typeof echarts === 'undefined') {
            return;
        }

        document.querySelectorAll('.fc-chart-canvas > div[id^="chart-panel-"], .fc-daily-chart[id^="chart-daily-"]').forEach(function (chartElement) {
            var chart = echarts.getInstanceByDom(chartElement);

            if (chart) {
                chart.resize();
            }
        });
    }

    function closeSurvey(prjValue, sidValue) {
        if (!confirm('Sei sicuro di voler chiudere questa ricerca?')) {
            return;
        }

        fetch(routes.closeSurvey, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                prj: prjValue,
                sid: sidValue
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    alert('Ricerca chiusa con successo!');
                    window.location.reload();
                    return;
                }

                alert('Errore: ' + (data.message || 'Operazione non riuscita.'));
            })
            .catch(function (error) {
                console.error(error);
                alert('Si è verificato un errore.');
            });
    }

    function reopenSurvey(prjValue, sidValue) {
        if (!confirm('Sei sicuro di voler riaprire questa ricerca?')) {
            return;
        }

        fetch(routes.reopenSurvey, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                prj: prjValue,
                sid: sidValue
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    alert('Ricerca riaperta con successo!');
                    window.location.reload();
                    return;
                }

                alert('Errore: ' + (data.message || 'Operazione non riuscita.'));
            })
            .catch(function (error) {
                console.error(error);
                alert('Si è verificato un errore.');
            });
    }

    function bindResetBloccate() {
        const button = document.getElementById('resetBloccateBtn');

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            const confirmed = confirm('⚠️ ATTENZIONE: Questa operazione NON è reversibile! Sei sicuro di voler resettare le interviste bloccate?');

            if (!confirmed) {
                return;
            }

            fetch(routes.resetBloccate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    prj: prj,
                    sid: sid
                })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        alert(data.resetCount + ' interviste sono state resettate e riabilitate.');
                        window.location.reload();
                        return;
                    }

                    alert('Errore: ' + (data.message || 'Operazione non riuscita.'));
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Si è verificato un errore durante il reset.');
                });
        });
    }

    function bindRedirectionsModal() {
        const saveAllBtn = document.getElementById('redirectionsSaveAllBtn');
        const saveAllStatus = document.getElementById('redirectionsSaveAllStatus');
        const tableBody = document.getElementById('redirectionsTableBody');

        if (!tableBody || !routes.updateRedirections) {
            return;
        }

        function showRowStatus(index, success, message) {
            const el = tableBody.querySelector('.fc-redirect-row-status[data-index="' + index + '"]');
            if (!el) {
                return;
            }

            el.textContent = success ? 'Salvato' : (message || 'Errore');
            el.classList.toggle('is-error', !success);
            el.classList.add('is-visible');

            setTimeout(function () {
                el.classList.remove('is-visible');
            }, 2500);
        }

        function showAllStatus(success, message) {
            if (!saveAllStatus) {
                return;
            }

            saveAllStatus.textContent = message || (success ? 'Tutte le modifiche sono state salvate.' : 'Errore durante il salvataggio.');
            saveAllStatus.classList.toggle('is-error', !success);
        }

        function sendUpdates(updates) {
            return fetch(routes.updateRedirections, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    prj: prj,
                    sid: sid,
                    updates: updates
                })
            }).then(function (response) {
                return response.json();
            });
        }

        tableBody.addEventListener('click', function (event) {
            const deleteBtn = event.target.closest('.fc-redirect-delete-group');
            if (deleteBtn) {
                const parameterName = deleteBtn.getAttribute('data-parameter-name');
                const parameterValue = deleteBtn.getAttribute('data-parameter-value');
                const groupLabel = deleteBtn.getAttribute('data-group-label');

                if (!routes.deleteRedirectionsGroup) {
                    return;
                }

                if (!confirm('Eliminare tutte le redirection del panel "' + groupLabel + '"? L\'operazione non è reversibile.')) {
                    return;
                }

                fetch(routes.deleteRedirectionsGroup, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        prj: prj,
                        sid: sid,
                        parameter_name: parameterName,
                        parameter_value: parameterValue
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            window.location.reload();
                            return;
                        }

                        alert(data.message || 'Errore durante l\'eliminazione.');
                    })
                    .catch(function (error) {
                        console.error(error);
                        alert('Errore di rete durante l\'eliminazione.');
                    });

                return;
            }

            const btn = event.target.closest('.fc-redirect-save-row');
            if (!btn) {
                return;
            }

            const index = btn.getAttribute('data-index');
            const input = tableBody.querySelector('.fc-redirect-input[data-index="' + index + '"]');

            if (!input) {
                return;
            }

            const redirectTo = input.value.trim();
            if (redirectTo === '') {
                showRowStatus(index, false, 'Il link non può essere vuoto');
                return;
            }

            sendUpdates([{ index: parseInt(index, 10), redirect_to: redirectTo }])
                .then(function (data) {
                    showRowStatus(index, !!data.success, data.message);
                })
                .catch(function (error) {
                    console.error(error);
                    showRowStatus(index, false, 'Errore di rete');
                });
        });

        if (saveAllBtn) {
            saveAllBtn.addEventListener('click', function () {
                const updates = [];

                tableBody.querySelectorAll('.fc-redirect-input').forEach(function (input) {
                    const value = input.value.trim();
                    if (value !== '') {
                        updates.push({ index: parseInt(input.getAttribute('data-index'), 10), redirect_to: value });
                    }
                });

                if (!updates.length) {
                    showAllStatus(false, 'Nessun link da salvare.');
                    return;
                }

                sendUpdates(updates)
                    .then(function (data) {
                        showAllStatus(!!data.success, data.message);

                        if (data.success) {
                            updates.forEach(function (update) {
                                showRowStatus(update.index, true);
                            });
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                        showAllStatus(false, 'Errore di rete');
                    });
            });
        }
    }

    function bindRedirectImport() {
        const analyzeBtn = document.getElementById('redirectImportAnalyzeBtn');
        const textarea = document.getElementById('redirectImportTextarea');
        const reviewWrap = document.getElementById('redirectImportReviewWrap');
        const reviewBody = document.getElementById('redirectImportReviewBody');
        const confirmBtn = document.getElementById('redirectImportConfirmBtn');
        const statusEl = document.getElementById('redirectImportStatus');
        const panelNamesMap = config.panelNamesMap || {};

        if (!analyzeBtn || !textarea || !reviewBody) {
            return;
        }

        const exitOptions = [
            { value: '', label: '-- scegli --' },
            { value: '3', label: 'Completa' },
            { value: '4', label: 'Non in target' },
            { value: '5', label: 'Quotafull' },
            { value: '7', label: 'Stop Int' },
            { value: '10', label: 'Bad Quality' }
        ];

        function buildExitSelect(selectedValue) {
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm fc-redirect-import-exit';

            exitOptions.forEach(function (opt) {
                const optionEl = document.createElement('option');
                optionEl.value = opt.value;
                optionEl.textContent = opt.label;
                if (selectedValue !== null && String(selectedValue) === opt.value) {
                    optionEl.selected = true;
                }
                select.appendChild(optionEl);
            });

            return select;
        }

        function getDefaultPanelValue() {
            const customInput = document.getElementById('redirectImportPanelCustom');
            const customValue = customInput ? customInput.value.trim() : '';
            if (customValue !== '') {
                return customValue;
            }

            const globalSelect = document.getElementById('redirectImportPanelSelect');
            return globalSelect ? globalSelect.value : '';
        }

        function buildPanelSelect(defaultValue) {
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm fc-redirect-import-panel';

            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '-- panel --';
            select.appendChild(emptyOpt);

            let hasMatch = false;

            Object.keys(panelNamesMap).forEach(function (code) {
                const optionEl = document.createElement('option');
                optionEl.value = code;
                optionEl.textContent = panelNamesMap[code];
                if (defaultValue && String(defaultValue) === String(code)) {
                    optionEl.selected = true;
                    hasMatch = true;
                }
                select.appendChild(optionEl);
            });

            // Codice personalizzato (non presente nell'elenco panel noti)
            if (defaultValue && !hasMatch) {
                const customOpt = document.createElement('option');
                customOpt.value = defaultValue;
                customOpt.textContent = 'Codice #' + defaultValue;
                customOpt.selected = true;
                select.appendChild(customOpt);
            }

            return select;
        }

        function clearStatus() {
            statusEl.textContent = '';
            statusEl.classList.remove('is-error');
        }

        function setStatus(success, message) {
            statusEl.textContent = message || '';
            statusEl.classList.toggle('is-error', !success);
        }

        analyzeBtn.addEventListener('click', function () {
            const text = textarea.value.trim();

            if (!text || !routes.parseRedirections) {
                return;
            }

            fetch(routes.parseRedirections, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ text: text })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || 'Nessuna redirection riconosciuta in questo testo.');
                        return;
                    }

                    reviewBody.innerHTML = '';

                    data.candidates.forEach(function (candidate) {
                        const card = document.createElement('div');
                        card.className = 'fc-redirect-import-row';
                        if (candidate.needs_review) {
                            card.classList.add('fc-redirect-import-needs-review-row');
                        }

                        const head = document.createElement('div');
                        head.className = 'fc-redirect-import-row-head';

                        const checkLabel = document.createElement('label');
                        checkLabel.className = 'fc-redirect-import-check';
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'form-check-input fc-redirect-import-include';
                        checkbox.checked = true;
                        const labelText = document.createElement('span');
                        labelText.className = 'fc-redirect-import-raw-label';
                        labelText.textContent = candidate.raw_label;
                        checkLabel.appendChild(checkbox);
                        checkLabel.appendChild(labelText);

                        if (candidate.needs_review) {
                            const badge = document.createElement('span');
                            badge.className = 'fc-redirect-import-review-badge';
                            badge.textContent = 'da rivedere';
                            checkLabel.appendChild(badge);
                        }

                        const controls = document.createElement('div');
                        controls.className = 'fc-redirect-import-row-controls';
                        controls.appendChild(buildExitSelect(candidate.exit_code));
                        controls.appendChild(buildPanelSelect(getDefaultPanelValue()));

                        head.appendChild(checkLabel);
                        head.appendChild(controls);

                        const linkWrap = document.createElement('div');
                        linkWrap.className = 'fc-redirect-import-row-link';
                        const linkInput = document.createElement('input');
                        linkInput.type = 'text';
                        linkInput.className = 'form-control fc-redirect-import-link';
                        linkInput.value = candidate.redirect_to;
                        linkInput.spellcheck = false;
                        linkWrap.appendChild(linkInput);

                        card.appendChild(head);
                        card.appendChild(linkWrap);
                        reviewBody.appendChild(card);
                    });

                    reviewWrap.style.display = '';
                    clearStatus();
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Errore durante l\'analisi del testo.');
                });
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const entries = [];
                let hasError = false;

                reviewBody.querySelectorAll('.fc-redirect-import-row').forEach(function (row) {
                    row.classList.remove('fc-redirect-import-row-error');

                    const include = row.querySelector('.fc-redirect-import-include');
                    if (!include || !include.checked) {
                        return;
                    }

                    const exitSelect = row.querySelector('.fc-redirect-import-exit');
                    const panelSelect = row.querySelector('.fc-redirect-import-panel');
                    const linkInput = row.querySelector('.fc-redirect-import-link');

                    if (!exitSelect.value || !panelSelect.value || !linkInput.value.trim()) {
                        row.classList.add('fc-redirect-import-row-error');
                        hasError = true;
                        return;
                    }

                    entries.push({
                        parameter_name: 'pan',
                        parameter_value: panelSelect.value,
                        exit_code: parseInt(exitSelect.value, 10),
                        redirect_to: linkInput.value.trim()
                    });
                });

                if (hasError) {
                    setStatus(false, 'Completa esito, panel e link per tutte le righe selezionate (evidenziate in rosso).');
                    return;
                }

                if (!entries.length) {
                    setStatus(false, 'Seleziona almeno una riga da aggiungere.');
                    return;
                }

                fetch(routes.addRedirections, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ prj: prj, sid: sid, entries: entries })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            alert((data.added || entries.length) + ' redirection aggiunte con successo.');
                            window.location.reload();
                            return;
                        }

                        setStatus(false, data.message || 'Errore durante il salvataggio.');
                    })
                    .catch(function (error) {
                        console.error(error);
                        setStatus(false, 'Errore di rete.');
                    });
            });
        }
    }

    function exposeGlobalActions() {
        window.closeSurvey = function (prjValue, sidValue) {
            closeSurvey(prjValue, sidValue);
        };
        window.reopenSurvey = function (prjValue, sidValue) {
            reopenSurvey(prjValue, sidValue);
        };
    }

function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.custom-navbar [data-bs-toggle="dropdown"], .fc-header-actions [data-bs-toggle="dropdown"]');

    dropdownToggles.forEach(function (toggleEl) {
        const instance = bootstrap.Dropdown.getOrCreateInstance(toggleEl);

        toggleEl.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            dropdownToggles.forEach(function (otherToggle) {
                if (otherToggle !== toggleEl) {
                    const otherInstance = bootstrap.Dropdown.getInstance(otherToggle);
                    if (otherInstance) {
                        otherInstance.hide();
                    }
                }
            });

            const menu = toggleEl.nextElementSibling;
            const isOpen = menu && menu.classList.contains('show');

            if (isOpen) {
                instance.hide();
            } else {
                instance.show();
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.custom-navbar .dropdown') && !event.target.closest('.fc-header-actions .dropdown')) {
            dropdownToggles.forEach(function (toggleEl) {
                const instance = bootstrap.Dropdown.getInstance(toggleEl);
                if (instance) {
                    instance.hide();
                }
            });
        }
    });
}

    document.addEventListener('DOMContentLoaded', function () {
        initTooltips();
        initDropdowns();
        initProgressBars();
        initCharts();
        initDailyCharts();
        bindResetBloccate();
        bindRedirectionsModal();
        bindRedirectImport();
        exposeGlobalActions();

        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (trigger) {
            trigger.addEventListener('shown.bs.tab', function () {
                resizeVisibleCharts();
            });
        });

        window.addEventListener('resize', resizeVisibleCharts);
    });
})();
