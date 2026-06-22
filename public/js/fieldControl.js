(function () {
    'use strict';

    const config = window.FieldControlConfig || {};
    const csrfToken = config.csrfToken || '';
    const prj = config.prj || '';
    const sid = config.sid || '';
    const routes = config.routes || {};
    const chartsData = config.filtrateCountsByPanel || {};

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

        const maxVisibleFiltrateRows = 7;
        const minPercentageForDenseLabels = 1;
        const denseLabelsThreshold = 6;
        const rowHeight = 44;
        const chartVerticalPadding = 36;

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
                    top: 28,
                    right: hasDenseBars ? 60 : 44,
                    bottom: 12,
                    left: 52,
                    containLabel: false
                },
                legend: {
                    top: 0,
                    right: 0,
                    textStyle: {
                        color: '#374151',
                        fontSize: 11
                    },
                    data: ['Filtrate']
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
                    axisLabel: {
                        color: '#4b5563',
                        fontSize: 11
                    },
                    splitLine: {
                        lineStyle: {
                            color: '#edf2f7'
                        }
                    }
                },
                yAxis: {
                    type: 'category',
                    inverse: true,
                    data: visibleLabels,
                    axisTick: {
                        show: false
                    },
                    axisLine: {
                        lineStyle: {
                            color: '#d7dee7'
                        }
                    },
                    axisLabel: {
                        color: '#1f2937',
                        fontSize: 11
                    }
                },
                series: [{
                    name: 'Filtrate',
                    type: 'bar',
                    data: visibleValues,
                    barMaxWidth: 24,
                    label: {
                        show: true,
                        position: 'right',
                        color: '#1f2937',
                        fontSize: 9,
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
                        color: '#7bd87d',
                        borderColor: '#2E7D32',
                        borderWidth: 1,
                        borderRadius: [0, 6, 6, 0]
                    }
                }]
            });

            chart.resize();
        });
    }

    function resizeVisibleCharts() {
        if (typeof echarts === 'undefined') {
            return;
        }

        document.querySelectorAll('.fc-chart-canvas > div[id^="chart-panel-"]').forEach(function (chartElement) {
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

    function exposeGlobalActions() {
        window.closeSurvey = function (prjValue, sidValue) {
            closeSurvey(prjValue, sidValue);
        };
    }

function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.custom-navbar [data-bs-toggle="dropdown"]');

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
        if (!event.target.closest('.custom-navbar .dropdown')) {
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
        bindResetBloccate();
        exposeGlobalActions();

        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (trigger) {
            trigger.addEventListener('shown.bs.tab', function () {
                resizeVisibleCharts();
            });
        });

        window.addEventListener('resize', resizeVisibleCharts);
    });
})();
