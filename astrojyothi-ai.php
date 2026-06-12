<?php
/**
 * Plugin Name: AstroJyothi AI Chat
 * Description: Krishnalaya AI Chat - Hybrid AI (Ollama local + Claude API) astrology assistant. Shortcode: [astrojyothi_ai_chat]
 * Version: 2.1
 * Author: Ravichandran Murugesan
 */
if (!defined('ABSPATH')) { exit; }

// ==================== Settings ====================

function ajai_get_settings() {
    $defaults = array(
        'provider'      => 'auto',                 // auto | ollama | claude
        'ollama_model'  => 'openhermes:latest',
        'claude_api_key'=> '',
        'claude_model'  => 'claude-haiku-4-5-20251001',
        'num_predict'   => 350,
    );
    $saved = get_option('ajai_settings', array());
    return wp_parse_args($saved, $defaults);
}

add_action('admin_menu', function () {
    add_options_page('AstroJyothi AI', 'AstroJyothi AI', 'manage_options', 'astrojyothi-ai', 'ajai_settings_page');
});

function ajai_settings_page() {
    if (!current_user_can('manage_options')) { return; }

    if (isset($_POST['ajai_save']) && check_admin_referer('ajai_settings_save')) {
        $s = array(
            'provider'       => in_array($_POST['provider'] ?? '', array('auto','ollama','claude'), true) ? $_POST['provider'] : 'auto',
            'ollama_model'   => sanitize_text_field($_POST['ollama_model'] ?? 'openhermes:latest'),
            'claude_api_key' => sanitize_text_field($_POST['claude_api_key'] ?? ''),
            'claude_model'   => sanitize_text_field($_POST['claude_model'] ?? 'claude-haiku-4-5-20251001'),
            'num_predict'    => max(50, min(2000, intval($_POST['num_predict'] ?? 350))),
        );
        update_option('ajai_settings', $s);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $s = ajai_get_settings();
    ?>
    <div class="wrap">
      <h1>AstroJyothi AI Settings</h1>
      <form method="post">
        <?php wp_nonce_field('ajai_settings_save'); ?>
        <table class="form-table">
          <tr>
            <th>Provider Mode</th>
            <td>
              <select name="provider">
                <option value="auto"   <?php selected($s['provider'], 'auto'); ?>>Auto (Tamil &rarr; Claude, English &rarr; Ollama)</option>
                <option value="ollama" <?php selected($s['provider'], 'ollama'); ?>>Ollama only (local, free)</option>
                <option value="claude" <?php selected($s['provider'], 'claude'); ?>>Claude API only (best quality)</option>
              </select>
            </td>
          </tr>
          <tr>
            <th>Ollama Model</th>
            <td>
              <input type="text" name="ollama_model" value="<?php echo esc_attr($s['ollama_model']); ?>" class="regular-text">
              <p class="description">Installed: openhermes:latest (7B), llama3.2:latest (3B)</p>
            </td>
          </tr>
          <tr>
            <th>Ollama Max Tokens</th>
            <td>
              <input type="number" name="num_predict" value="<?php echo esc_attr($s['num_predict']); ?>" min="50" max="2000">
              <p class="description">Response length limit. Lower = faster, avoids timeouts. Recommended: 300-400</p>
            </td>
          </tr>
          <tr>
            <th>Claude API Key</th>
            <td>
              <input type="password" name="claude_api_key" value="<?php echo esc_attr($s['claude_api_key']); ?>" class="regular-text" autocomplete="off">
              <p class="description">Required for Auto / Claude modes. Get from console.anthropic.com</p>
            </td>
          </tr>
          <tr>
            <th>Claude Model</th>
            <td><input type="text" name="claude_model" value="<?php echo esc_attr($s['claude_model']); ?>" class="regular-text"></td>
          </tr>
        </table>
        <p><input type="submit" name="ajai_save" class="button button-primary" value="Save Settings"></p>
      </form>
    </div>
    <?php
}

// ==================== Helpers ====================

function ajai_system_prompt() {
    return 'You are AstroJyothi AI, an expert Vedic astrology assistant for Krishnalaya Astro Centre. '
         . 'You know Jyotish, Panchang, Dasa systems, Jamakol Prasnam, Pancha Pakshi and numerology. '
         . 'Answer concisely (under 200 words) in PLAIN TEXT only - no markdown symbols like # or asterisks. '
         . 'Reply in the same language the user used. '
         . 'CRITICAL: when replying in Tamil, use ONLY pure Tamil script - never mix Devanagari, Bengali, Telugu or any other script. '
         . 'If the message contains [PANCHANG DATA], treat it as the authoritative Swiss Ephemeris calculation: '
         . 'use ONLY those values for panchang facts and never invent or guess panchang values.';
}

function ajai_is_indic($text) {
    // Tamil, Devanagari, Telugu, Kannada, Malayalam unicode ranges
    return (bool) preg_match('/[\x{0B80}-\x{0BFF}\x{0900}-\x{097F}\x{0C00}-\x{0C7F}\x{0C80}-\x{0CFF}\x{0D00}-\x{0D7F}]/u', $text);
}

function ajai_needs_panchang($text) {
    $keywords = array(
        'பஞ்சாங்க', 'திதி', 'நட்சத்திர', 'யோக', 'கரண', 'கிழமை', 'வாரம்',
        'இன்று', 'இன்றைய', 'ராகு கால', 'எமகண்ட', 'குளிகை', 'உதயம்', 'அஸ்தமன',
        'panchang', 'tithi', 'nakshatra', 'yoga', 'karana', 'rahu kala',
        'sunrise', 'sunset', 'today'
    );
    foreach ($keywords as $kw) {
        if (stripos($text, $kw) !== false) {
            return true;
        }
    }
    return false;
}

function ajai_fetch_panchang() {
    // AstroJyothi v2 API - same VPS, localhost call (Vellore coordinates)
    $response = wp_remote_get('http://127.0.0.1:3100/api/panchang?lat=12.9165&lng=79.1325', array(
        'timeout' => 15,
    ));
    if (is_wp_error($response)) {
        return null;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['success']) || empty($data['data'])) {
        return null;
    }
    return $data['data'];
}

// ==================== Providers ====================

function ajai_call_ollama($message, $s) {
    $response = wp_remote_post('http://127.0.0.1:11434/api/chat', array(
        'timeout' => 120,
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => wp_json_encode(array(
            'model'    => $s['ollama_model'],
            'stream'   => false,
            'options'  => array('num_predict' => intval($s['num_predict'])),
            'messages' => array(
                array('role' => 'system', 'content' => ajai_system_prompt()),
                array('role' => 'user',   'content' => $message),
            ),
        )),
    ));

    if (is_wp_error($response)) {
        return array('ok' => false, 'error' => 'Ollama: ' . $response->get_error_message());
    }
    $data  = json_decode(wp_remote_retrieve_body($response), true);
    $reply = isset($data['message']['content']) ? trim($data['message']['content']) : '';
    if ($reply === '') {
        return array('ok' => false, 'error' => 'Ollama: empty reply');
    }
    return array('ok' => true, 'reply' => $reply, 'provider' => 'ollama (' . $s['ollama_model'] . ')');
}

function ajai_call_claude($message, $s) {
    if (empty($s['claude_api_key'])) {
        return array('ok' => false, 'error' => 'Claude: API key not set');
    }
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
        'timeout' => 60,
        'headers' => array(
            'Content-Type'      => 'application/json',
            'x-api-key'         => $s['claude_api_key'],
            'anthropic-version' => '2023-06-01',
        ),
        'body' => wp_json_encode(array(
            'model'      => $s['claude_model'],
            'max_tokens' => 1024,
            'system'     => ajai_system_prompt(),
            'messages'   => array(
                array('role' => 'user', 'content' => $message),
            ),
        )),
    ));

    if (is_wp_error($response)) {
        return array('ok' => false, 'error' => 'Claude: ' . $response->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200) {
        $msg = isset($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $code);
        return array('ok' => false, 'error' => 'Claude: ' . $msg);
    }
    $reply = '';
    if (!empty($data['content']) && is_array($data['content'])) {
        foreach ($data['content'] as $block) {
            if (isset($block['type']) && $block['type'] === 'text') {
                $reply .= $block['text'];
            }
        }
    }
    $reply = trim($reply);
    if ($reply === '') {
        return array('ok' => false, 'error' => 'Claude: empty reply');
    }
    return array('ok' => true, 'reply' => $reply, 'provider' => 'claude (' . $s['claude_model'] . ')');
}

// ==================== AJAX Handler ====================

add_action('wp_ajax_ajai_chat', 'ajai_handle_chat');
add_action('wp_ajax_nopriv_ajai_chat', 'ajai_handle_chat');

function ajai_handle_chat() {
    check_ajax_referer('ajai_nonce', 'nonce');

    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if ($message === '') {
        wp_send_json_error(array('error' => 'Empty message'));
    }

    $s = ajai_get_settings();

    // Panchang grounding: attach real calculated data when relevant
    $augmented = $message;
    if (ajai_needs_panchang($message)) {
        $panchang = ajai_fetch_panchang();
        if ($panchang) {
            $augmented = "[PANCHANG DATA - Swiss Ephemeris, Vellore, today]\n"
                       . wp_json_encode($panchang, JSON_UNESCAPED_UNICODE)
                       . "\n[End of data. Use ONLY these values for panchang facts.]\n\n"
                       . "User question: " . $message;
        }
    }

    // Decide provider
    $mode = $s['provider'];
    if ($mode === 'auto') {
        $mode = (ajai_is_indic($message) && !empty($s['claude_api_key'])) ? 'claude' : 'ollama';
    }

    // Primary call
    $result = ($mode === 'claude') ? ajai_call_claude($augmented, $s) : ajai_call_ollama($augmented, $s);

    // Fallback to the other provider on failure (auto mode only)
    if (!$result['ok'] && $s['provider'] === 'auto') {
        $result = ($mode === 'claude') ? ajai_call_ollama($augmented, $s) : ajai_call_claude($augmented, $s);
    }

    if (!$result['ok']) {
        wp_send_json_error(array('error' => $result['error']));
    }

    wp_send_json_success(array('reply' => $result['reply'], 'provider' => $result['provider']));
}

// ==================== Shortcode UI ====================

add_shortcode('astrojyothi_ai_chat', 'ajai_render_chat');

function ajai_render_chat() {
    $nonce = wp_create_nonce('ajai_nonce');
    $ajax  = admin_url('admin-ajax.php');
    ob_start();
    ?>
    <div id="ajai-box" style="max-width:700px;margin:20px auto;background:#fff;border-radius:14px;box-shadow:0 5px 20px rgba(0,0,0,0.12);overflow:hidden;font-family:Arial,sans-serif;">
      <div style="background:#d84315;color:#fff;padding:14px 20px;font-size:18px;font-weight:bold;">
        AstroJyothi AI - கிருஷ்ணாலயா ஜோதிட உதவியாளர்
      </div>
      <div id="ajai-msgs" style="height:380px;overflow-y:auto;padding:16px;background:#fdf6ee;"></div>
      <div style="display:flex;gap:8px;padding:12px;border-top:1px solid #eee;">
        <input id="ajai-input" type="text" placeholder="உங்கள் கேள்வியை இங்கே எழுதுங்கள்..."
          style="flex:1;padding:11px;border:1px solid #ccc;border-radius:8px;font-size:15px;">
        <button id="ajai-send"
          style="background:#d84315;color:#fff;border:none;padding:11px 22px;border-radius:8px;cursor:pointer;font-size:15px;">
          அனுப்பு
        </button>
      </div>
    </div>
    <script>
    (function(){
      var ajaxUrl = '<?php echo esc_js($ajax); ?>';
      var nonce   = '<?php echo esc_js($nonce); ?>';
      var msgs  = document.getElementById('ajai-msgs');
      var input = document.getElementById('ajai-input');
      var btn   = document.getElementById('ajai-send');

      function addMsg(text, who){
        var d = document.createElement('div');
        d.style.cssText = 'margin:8px 0;padding:10px 14px;border-radius:10px;max-width:85%;white-space:pre-wrap;line-height:1.5;'
          + (who === 'user' ? 'background:#d84315;color:#fff;margin-left:auto;' : 'background:#fff;border:1px solid #eee;');
        d.textContent = text;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
      }

      function send(){
        var text = input.value.trim();
        if(!text) return;
        addMsg(text, 'user');
        input.value = '';
        btn.disabled = true;
        var waiting = addMsg('யோசிக்கிறேன்... ⏳', 'ai');

        var fd = new FormData();
        fd.append('action', 'ajai_chat');
        fd.append('nonce', nonce);
        fd.append('message', text);

        fetch(ajaxUrl, { method: 'POST', body: fd })
          .then(function(r){
            if (!r.ok) { throw new Error('Server error HTTP ' + r.status + ' (timeout?)'); }
            return r.json();
          })
          .then(function(j){
            if (j.success) {
              waiting.textContent = j.data.reply;
              waiting.title = j.data.provider || '';
            } else {
              waiting.textContent = 'பிழை: ' + ((j.data && j.data.error) ? j.data.error : 'unknown');
            }
          })
          .catch(function(e){ waiting.textContent = 'பிழை: ' + e.message; })
          .finally(function(){ btn.disabled = false; input.focus(); });
      }

      btn.addEventListener('click', send);
      input.addEventListener('keydown', function(e){ if(e.key === 'Enter') send(); });
      addMsg('வணக்கம்! ஜோதிடம் தொடர்பான உங்கள் கேள்விகளை கேளுங்கள். 🙏', 'ai');
    })();
    </script>
    <?php
    return ob_get_clean();
}

require_once plugin_dir_path(__FILE__) . 'upagraha-widget.php';
require_once plugin_dir_path(__FILE__) . 'panchangam-light.php';

require_once plugin_dir_path(__FILE__) . 'shadbala-page.php';

require_once plugin_dir_path(__FILE__) . 'dasa-page.php';
