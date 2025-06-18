<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Esign\ConversionsApi\Facades\ConversionsApi;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

class TestServerSide extends Command
{
    protected $signature = 'test:server-side';
    protected $description = 'Testar envio server-side para Facebook Conversions API';

    public function handle()
    {
        $this->info('🧪 TESTE SERVER-SIDE FACEBOOK CONVERSIONS API');
        $this->info('===============================================');

        try {
            // Criar evento de teste
            $event = (new Event())
                ->setActionSource(ActionSource::WEBSITE)
                ->setEventName('PageView')
                ->setEventTime(time())
                ->setEventId('test-' . uniqid())
                ->setEventSourceUrl('https://salveterrah.com.br/test');
            
            // Dados do usuário
            $userData = (new UserData())
                ->setClientIpAddress('127.0.0.1')
                ->setClientUserAgent('Test User Agent')
                ->setFbc('fb.2.1749937476159.Test')
                ->setFbp('fb.2.1749927541960.Test')
                ->setExternalId('test-user-123');
            
            $event->setUserData($userData);
            
            // Dados customizados
            $customData = (new CustomData())
                ->setContentIds(['test-product-123'])
                ->setContentType('product')
                ->setValue(99.99)
                ->setCurrency('BRL');
            
            $event->setCustomData($customData);
            
            $this->info('📊 DADOS DO EVENTO:');
            $this->info('- Action Source: ' . $event->getActionSource());
            $this->info('- Event Name: ' . $event->getEventName());
            $this->info('- Event ID: ' . $event->getEventId());
            $this->info('- Event Time: ' . $event->getEventTime());
            $this->info('- Event Source URL: ' . $event->getEventSourceUrl());
            $this->info('');
            
            // Enviar evento
            $this->info('🚀 ENVIANDO EVENTO PARA FACEBOOK...');
            
            $response = ConversionsApi::addEvent($event)->sendEvents();
            
            $this->info('✅ EVENTO ENVIADO COM SUCESSO!');
            $this->info('📊 Response: ' . json_encode($response, JSON_PRETTY_PRINT));
            
            $this->info('');
            $this->info('===============================================');
            $this->info('🔍 VERIFIQUE O FACEBOOK EVENTS MANAGER EM 5-10 MINUTOS');
            $this->info('📊 Procure pelo evento com ID: ' . $event->getEventId());
            $this->info('🎯 Deve aparecer como "API de Conversões" e não "Navegador"');
            
        } catch (\Exception $e) {
            $this->error('❌ ERRO AO ENVIAR EVENTO:');
            $this->error('- Mensagem: ' . $e->getMessage());
            $this->error('- Arquivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('- Trace: ' . $e->getTraceAsString());
        }
    }
} 