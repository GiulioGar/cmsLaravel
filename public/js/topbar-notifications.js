document.addEventListener('DOMContentLoaded', function () {
    var totalRequests = document.getElementById('totalRequests');
    var totalRequestsText = document.getElementById('totalRequestsText');
    var amazonRequests = document.getElementById('amazonRequests');
    var paypalRequests = document.getElementById('paypalRequests');
    var openTickets = document.getElementById('openTickets');
    var runningSurveys = document.getElementById('runningSurveys');

if (!totalRequests || !totalRequestsText || !amazonRequests || !paypalRequests || !openTickets || !runningSurveys) {
    console.warn('Topbar notifications: elementi DOM mancanti.');
    return;
}

    function updateCountStyle(element, value) {
        element.textContent = value;
        element.classList.remove('has-items');

        if (value > 0) {
            element.classList.add('has-items');
        }
    }

    function updateNotifications() {
        fetch('/notifications/summary', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Errore HTTP: ' + response.status);
            }

            return response.json();
        })
        .then(function (result) {
            if (!result || result.success !== true || !result.data) {
                console.warn('Topbar notifications: risposta non valida.', result);
                return;
            }

            var data = result.data;

            var total = parseInt(data.total, 10) || 0;
            var amazon = parseInt(data.amazon_rewards, 10) || 0;
            var paypal = parseInt(data.paypal_rewards, 10) || 0;
            var tickets = parseInt(data.open_tickets, 10) || 0;
            var surveys = parseInt(data.running_surveys, 10) || 0;

            totalRequests.textContent = total;
            totalRequestsText.textContent = total;
            runningSurveys.textContent = surveys;

            updateCountStyle(amazonRequests, amazon);
            updateCountStyle(paypalRequests, paypal);
            updateCountStyle(openTickets, tickets);

            if (total > 0) {
                totalRequests.classList.remove('d-none');
                totalRequests.classList.add('is-active');
            } else {
                totalRequests.classList.add('d-none');
                totalRequests.classList.remove('is-active');
            }
        })
        .catch(function (error) {
            console.error('Topbar notifications error:', error);
        });
    }

    updateNotifications();
    setInterval(updateNotifications, 60000);

    if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
    }
});
