# Servier Scheduler

Questo progetto è un'applicazione Laravel con frontend Vite/Vue (Inertia). 
Include un sistema automatizzato per lo sviluppo locale e la condivisione pubblica istantanea tramite Cloudflare Tunnel.

## Requisiti

- **PHP 8.3+**
- **Composer**
- **Node.js & NPM**
- **Cloudflared** (Il file eseguibile deve essere presente nella root del progetto oppure installato globalmente)

## Installazione per i Collaboratori

Se hai appena clonato il progetto da GitHub, segui questi semplici passaggi per configurare l'ambiente sul tuo PC:

1. **Installa le dipendenze backend (PHP):**
   ```bash
   composer install
   ```

2. **Installa le dipendenze frontend (Node):**
   ```bash
   npm install
   ```

3. **Crea il file di configurazione e genera la chiave:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Prepara il Database:**
   *(Assicurati di aver configurato i parametri del database nel file `.env`)*
   ```bash
   php artisan migrate
   ```

---

## Avvio dell'ambiente di Sviluppo (Comando Rapido)

Per facilitare lo sviluppo e testare l'app in contemporanea su PC e Smartphone (senza conflitti di porte o problemi di HTTPS), abbiamo creato un comando personalizzato che avvia tutto il necessario.

Dal terminale, esegui semplicemente:

```bash
php artisan app:tunnel
```

### Cosa fa questo comando in automatico?
- **Server:** Avvia il server locale di Laravel sulla porta 8000 (`php artisan serve`).
- **Frontend (Vite):** Avvia la compilazione continua in background (`npm run build --watch`). Questo metodo garantisce che i file grafici vengano serviti staticamente, eliminando i classici problemi di "Mixed Content" e schermata bianca quando si testa da dispositivi mobile tramite proxy HTTPS.
- **Tunnel:** Avvia `cloudflared` ed espone il sito con un link pubblico e sicuro (es. `https://....trycloudflare.com`).
- **Auto-Configurazione:** Estrae il link generato da Cloudflare e lo inserisce in automatico nella variabile `APP_URL` del file `.env`.

### Come fermare l'ambiente
Quando hai finito di lavorare, **non chiudere brutalmente la finestra**. Clicca all'interno del terminale e premi la combinazione:
`Ctrl + C`
Il comando intercetterà il segnale e si occuperà di terminare in modo pulito tutti i processi (PHP, Vite e Cloudflared), lasciando le porte libere per il prossimo avvio.

---

## Note Tecniche (CSP e Sicurezza)
Il progetto utilizza un middleware dedicato (`App\Http\Middleware\SecurityHeaders.php`) che inietta Content-Security-Policy (CSP) stringenti.
Se l'ambiente nel `.env` è impostato su `local`, le regole sono automaticamente allentate per permettere il corretto funzionamento degli script locali, quindi non richiede configurazioni extra da parte tua.
