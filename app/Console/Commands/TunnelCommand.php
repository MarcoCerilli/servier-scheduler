<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('app:tunnel')]
#[Description('Avvia Server Laravel, Vite e Cloudflare Tunnel contemporaneamente')]
class TunnelCommand extends Command
{
    protected array $processes = [];

    public function handle()
    {
        $this->info('🚀 Avvio ambiente di sviluppo con Cloudflare Tunnel...');

        $this->processes = [
            'server' => new Process(['php', 'artisan', 'serve', '--host=0.0.0.0']),
            'vite' => new Process(['npm', 'run', 'dev']),
            'tunnel' => new Process(['./cloudflared', 'tunnel', '--url', 'http://localhost:8000']),
        ];

        // Avviamo tutti i processi
        foreach ($this->processes as $name => $process) {
            $process->setTimeout(null);
            $process->start();
            $this->info("✓ Processo [$name] avviato");
        }

        $urlFound = false;

        $this->warn("Attendo la connessione di Cloudflare...");

        // Loop per leggere l'output e tenerli in vita
        while (true) {
            $allFinished = true;
            
            foreach ($this->processes as $name => $process) {
                if ($process->isRunning()) {
                    $allFinished = false;
                }

                // Leggiamo l'output normale
                if ($output = $process->getIncrementalOutput()) {
                    $this->printProcessOutput($name, $output);
                }
                
                // Cloudflared di solito scrive l'output su "Error Output" (stderr)
                if ($errOutput = $process->getIncrementalErrorOutput()) {
                    $this->printProcessOutput($name, $errOutput);
                    
                    // Cerchiamo l'URL di trycloudflare se non l'abbiamo ancora trovato
                    if (!$urlFound && $name === 'tunnel') {
                        if (preg_match('/https:\/\/[a-zA-Z0-9.-]*\.trycloudflare\.com/', $errOutput, $matches)) {
                            $url = $matches[0];
                            $urlFound = true;
                            
                            $this->newLine();
                            $this->info("==============================================");
                            $this->info("✅ Tunnel connesso con successo!");
                            $this->info("🔗 URL PUBBLICO: $url");
                            $this->info("==============================================");
                            $this->newLine();
                            
                            // Aggiorniamo il file .env
                            $envPath = base_path('.env');
                            if (file_exists($envPath)) {
                                $envContent = file_get_contents($envPath);
                                $envContent = preg_replace('/^APP_URL=.*$/m', 'APP_URL='.$url, $envContent);
                                file_put_contents($envPath, $envContent);
                                
                                \Illuminate\Support\Facades\Artisan::call('config:clear');
                                $this->info("⚙️  Il file .env è stato aggiornato con il nuovo URL.");
                                $this->warn("🟢 Premi Ctrl+C sul terminale (o usa il tasto Stop dell'IDE) per fermare tutti i servizi.");
                            }
                        }
                    }
                }
            }

            if ($allFinished) {
                break;
            }

            usleep(200000); // Pausa di 0.2 secondi
        }
    }

    protected function printProcessOutput(string $name, string $output)
    {
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                $this->line("<comment>[$name]</comment> $line");
            }
        }
    }

    public function __destruct()
    {
        // Se chiudiamo il comando, uccidiamo tutti i processi child
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }
}
