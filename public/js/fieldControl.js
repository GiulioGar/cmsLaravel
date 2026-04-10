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

        Object.keys(chartsData).forEach(function (panelName) {
            const panelRows = chartsData[panelName];
            const labels = [];
            const values = [];
            const tooltips = [];

            if (!panelRows) {
                return;
            }

            Object.entries(panelRows).forEach(function (entry) {
                const question = entry[0];
                const count = entry[1];

                const parts = question.split(' - ');
                const questionCode = parts[0] || 'N/A';
                const questionText = parts.slice(1).join(' - ') || 'Testo non disponibile';

                labels.push(questionCode);
                values.push(count);
                tooltips.push({
                    code: questionCode,
                    text: questionText
                });
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
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function () {
                                    return null;
                                },
                                label: function (context) {
                                    const dataIndex = context.dataIndex;
                                    const questionData = tooltips[dataIndex];

                                    if (!questionData) {
                                        return 'Dati non disponibili';
                                    }

                                    const truncatedText = questionData.text.length > 80
                                        ? questionData.text.substring(0, 80) + '...'
                                        : questionData.text;

                                    return questionData.code + ': ' + truncatedText;
                                }
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
                                maxRotation: 45,
                                minRotation: 0
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
