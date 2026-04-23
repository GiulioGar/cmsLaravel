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

function initCharts() {
    if (typeof Chart === 'undefined') {
        return;
    }

    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    Object.keys(chartsData).forEach(function (panelName) {
        const panelRows = chartsData[panelName];
        const labels = [];
        const values = [];
        const questionTooltips = [];
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

        const canvasId = 'chart-panel-' + buildPanelSlug(panelName);
        const canvas = document.getElementById(canvasId);

        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Filtrate',
                    data: values,
                    backgroundColor: '#7bd87d',
                    borderColor: '#2E7D32',
                    borderWidth: 1,
                    percentages: percentages,
                    questionTooltips: questionTooltips
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 28
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                if (!items || !items.length) {
                                    return '';
                                }

                                const dataIndex = items[0].dataIndex;
                                const dataset = items[0].dataset;
                                const questionData = dataset.questionTooltips
                                    ? dataset.questionTooltips[dataIndex]
                                    : null;

                                return questionData ? questionData.code : '';
                            },
                            label: function (context) {
                                const dataIndex = context.dataIndex;
                                const dataset = context.dataset;
                                const questionData = dataset.questionTooltips
                                    ? dataset.questionTooltips[dataIndex]
                                    : null;

                                const lines = [];

                                function wrapText(text, maxLineLength, maxLines) {
                                    if (!text) {
                                        return [];
                                    }

                                    const words = String(text).split(/\s+/);
                                    const wrapped = [];
                                    let currentLine = '';

                                    words.forEach(function (word) {
                                        const testLine = currentLine ? (currentLine + ' ' + word) : word;

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
                                        const limited = wrapped.slice(0, maxLines);
                                        limited[maxLines - 1] = limited[maxLines - 1].replace(/\s*\.*$/, '') + '...';
                                        return limited;
                                    }

                                    return wrapped;
                                }

                                if (questionData) {
                                    const wrappedQuestionText = wrapText(questionData.text, 38, 4);
                                    lines.push.apply(lines, wrappedQuestionText);
                                }

                                lines.push('Filtrate: ' + context.raw);

                                const percentage = dataset.percentages
                                    ? dataset.percentages[dataIndex]
                                    : 0;

                                lines.push('% sul totale filtrate: ' + percentage.toFixed(1) + '%');

                                return lines;
                            }
                        }
                    },
                    datalabels: {
                        display: function (context) {
                            return context.dataset.data[context.dataIndex] > 0;
                        },
                        anchor: 'end',
                        align: 'end',
                        offset: -2,
                        clamp: true,
                        clip: false,
                        color: function (context) {
                            return context.dataIndex % 2 === 0 ? '#111827' : '#15803d';
                        },
                        font: {
                            weight: '700',
                            size: 9
                        },
                    formatter: function (value, context) {
                        const dataset = context.dataset;
                        const percentage = dataset.percentages
                            ? dataset.percentages[context.dataIndex]
                            : 0;

                        return percentage.toFixed(1) + '% ';
                    }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            callback: function (value, index) {
                                return labels[index] || '';
                            }
                        }
                    }
                }
            }
        });
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
    });
})();
