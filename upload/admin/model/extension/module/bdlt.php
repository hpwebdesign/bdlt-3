<?php
class ModelExtensionModuleBdlt extends Model {
    public function translateProduct($data, $target_lang = 'ID') {
        $api_key = $this->config->get('bdlt_setting_api_key');

        // PENTING: Gunakan salah satu sesuai akun Anda
        // Free
        $endpoint = 'https://api-free.deepl.com/v2/translate';
        if ($this->config->get('bdlt_setting_api_type')) {
        $endpoint = 'https://api.deepl.com/v2/translate';
        }
        
        // Pro
        // $endpoint = 'https://api.deepl.com/v2/translate';

        $translated_result = [];
        $custom_fields = isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [];

        unset($data['custom_fields']);

        foreach ($data as $key => $text) {
            $translated_result[$key] = $this->translateTextDeepL($text, $target_lang, $endpoint, $api_key, $key);
        }

        if (!empty($custom_fields)) {
            $translated_result['custom_fields'] = [];
            foreach ($custom_fields as $selector => $content) {
                $translated_result['custom_fields'][$selector] = $this->translateTextDeepL($content, $target_lang, $endpoint, $api_key, $selector);
            }
        }

        return $translated_result;
    }


    private function translateTextDeepL($text, $target_lang, $endpoint, $api_key, $label = 'field') {

        if (empty($text)) return '';
    
        $payload = [
            'text'        => $text,
            'target_lang' => strtoupper($target_lang),
            'tag_handling'=> 'html',
            'preserve_formatting' => 1
        ];
    
        $headers = [
            'Authorization: DeepL-Auth-Key ' . $api_key,
            'Content-Type: application/x-www-form-urlencoded'
        ];
    
        $ch = curl_init($endpoint);
    
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $this->log->write('CURL ERROR [' . $label . ']: ' . $error);
            throw new Exception('CURL Error: ' . $error);
        }
    
        curl_close($ch);
    
        $this->log->write('DeepL HTTP CODE [' . $label . ']: ' . $http_code);
        $this->log->write('DeepL RESPONSE [' . $label . ']: ' . $response);
    
        $result = json_decode($response, true);
    
        if ($http_code !== 200) {
            throw new Exception('DeepL HTTP Error ' . $http_code);
        }
    
        if (!isset($result['translations'][0]['text'])) {
            throw new Exception('Translation failed for "' . $label . '"');
        }
    
        return $result['translations'][0]['text'];
    }


}
