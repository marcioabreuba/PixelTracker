<?php

namespace App\Http\Controllers;

use App\Events\Purchase;
use App\Models\User;
use Esign\ConversionsApi\Facades\ConversionsApi;
use FacebookAds\Object\ServerSide\CustomData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\PixelLogger;

class HotmartController extends Controller
{
    public function Hotmart(Request $request)
    {
        try {
            $json = $request->input('data');
            
            // Log do início do processamento Hotmart
            PixelLogger::logEventStart('Purchase', $json, 'HOTMART');
            $fn = strtolower($json['buyer']['first_name'] ?? '');
            $ln = strtolower($json['buyer']['last_name'] ?? '');
            $em = strtolower($json['buyer']['email'] ?? '');
            $ph = preg_replace('/\D/', '', $json['buyer']['checkout_phone'] ?? '');
            $origin = $json['purchase']['origin']['xcod'] ?? '';
            $currency = $json['purchase']['full_price']['currency_value'] ?? 'BRL';
            $price = $json['purchase']['full_price']['value'] ?? 0;

            $user = User::where('external_id', $origin)->first();
            if ($user) {
                $user->update([
                    'fn' => $fn,
                    'ln' => $ln,
                    'em' => $em,
                    'ph' => $ph,
                ]);
            } else {
                $user = User::create([
                    'fn' => $fn,
                    'ln' => $ln,
                    'em' => $em,
                    'ph' => $ph,
                ]);
            }

            $contentId = $user->content_id ?? '';
            $external_id = $user->external_id ?? '';
            $client_ip_address = $user->client_ip_address ?? '';
            $client_user_agent = $user->client_user_agent ?? '';
            $fbp = $user->fbp ?? '';
            $fbc = $user->fbc ?? '';
            $country = $user->country ?? '';
            $st = $user->st ?? '';
            $ct = $user->ct ?? '';
            $zp = $user->zp ?? '';
            $fn = $user->fn ?? '';
            $ln = $user->ln ?? '';
            $em = $user->em ?? '';
            $ph = $user->ph ?? '';

            // Configurar pixel baseado no contentId
            $domains = config('conversions.domains');
            if (isset($domains[$contentId])) {
                $config = $domains[$contentId];
                Config::set('conversions-api.pixel_id', $config['pixel_id']);
                Config::set('conversions-api.access_token', $config['access_token']);
                Config::set('conversions-api.test_code', $config['test_code']);
                
                // Log da configuração do pixel
                PixelLogger::logPixelConfig($contentId, $config);
            } else {
                PixelLogger::logError('Configuração Pixel Hotmart', 'Content ID não encontrado: ' . $contentId);
            }

            $event = Purchase::create();
            $advancedMatching = $event->getUserData()
                ->setExternalId($external_id)
                ->setClientIpAddress($client_ip_address)
                ->setClientUserAgent($client_user_agent)
                ->setFbp($fbp)
                ->setFbc($fbc)
                ->setCountryCode($country)
                ->setState($st)
                ->setCity($ct)
                ->setZipCode($zp)
                ->setFirstName($fn)
                ->setLastName($ln)
                ->setEmail($em)
                ->setPhone($ph);
            $event->setUserData($advancedMatching);
            $event->setCustomData((new CustomData())->setContentIds([$contentId])->setCurrency($currency)->setValue($price));

            // Log específico do evento Hotmart
            PixelLogger::logShopifyEvent('Purchase', [
                'order_id' => 'hotmart_' . ($json['transaction'] ?? 'unknown'),
                'order_number' => $json['transaction'] ?? 'unknown',
                'total_price' => $price,
                'currency' => $currency,
                'customer_email' => $em
            ], [
                'external_id' => $external_id,
                'fn' => $fn,
                'ln' => $ln,
                'em' => $em,
                'ph' => $ph
            ], [
                'event_id' => $event->getEventId(),
                'pixel_id' => config('conversions-api.pixel_id'),
                'success' => true
            ]);

            ConversionsApi::addEvent($event)->sendEvents();

            // Log de sucesso
            PixelLogger::logEventSuccess('Purchase', $event->getEventId(), $external_id);

            return response()->json(['message' => 'Webhook processado com sucesso']);
        } catch (\Exception $e) {
            PixelLogger::logError('Webhook Hotmart', $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Erro interno no servidor'], 500);
        }
    }
}
