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
            'auth_key'    => $api_key,
            'text'        => $text,
            'target_lang' => strtoupper($target_lang),
            'tag_handling'=> 'html',
            'preserve_formatting' => 1
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->log->write('CURL ERROR: ' . curl_error($ch));
            throw new Exception('CURL Error on "' . $label . '": ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (!isset($result['translations'][0]['text'])) {
            $this->log->write('DeepL API ERROR for "' . $label . '": ' . print_r($result, true));
            throw new Exception('Translation failed for "' . $label . '"');
        }

        return $result['translations'][0]['text'];
    }


}
