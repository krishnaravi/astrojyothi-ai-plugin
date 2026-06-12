<?php
/**
 * AstroJyothi Upagraha Widget
 * Shortcode: [astrojyothi_upagraha]
 * Shows today's upagraha time table from AstroJyothi v2 API
 */
if (!defined('ABSPATH')) { exit; }

add_shortcode('astrojyothi_upagraha', 'ajai_render_upagraha');

function ajai_render_upagraha($atts) {
    $atts = shortcode_atts(array(
        'lat' => '12.9165',
        'lng' => '79.1325',
    ), $atts);

    $lat = floatval($atts['lat']);
    $lng = floatval($atts['lng']);

    // Fetch from AstroJyothi v2 API (localhost)
    $response = wp_remote_get(
        "http://127.0.0.1:3100/api/upagraha?lat={$lat}&lng={$lng}",
        array('timeout' => 15)
    );

    if (is_wp_error($response)) {
        return '<p style="color:red;">உபகிரக தகவல் கிடைக்கவில்லை.</p>';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['success'])) {
        return '<p style="color:red;">உபகிரக தகவல் பிழை: ' . esc_html($data['error'] ?? 'unknown') . '</p>';
    }

    $date      = $data['date'];
    $sunrise   = $data['sunrise'];
    $sunset    = $data['sunset'];
    $timeTable = $data['timeTable']  ?? array();
    $dhoomadi  = $data['dhoomadi']   ?? array();

    // Tamil weekday
    $weekdays = array('ஞாயிறு','திங்கள்','செவ்வாய்','புதன்','வியாழன்','வெள்ளி','சனி');
    $dow = date('w', strtotime($date));
    $weekday_tamil = $weekdays[$dow];

    $rasi_names = array(
        1=>'மேஷம்',2=>'ரிஷபம்',3=>'மிதுனம்',4=>'கடகம்',
        5=>'சிம்மம்',6=>'கன்னி',7=>'துலாம்',8=>'விருச்சிகம்',
        9=>'தனுசு',10=>'மகரம்',11=>'கும்பம்',12=>'மீனம்'
    );

    ob_start();
    ?>
    <div class="ajai-upa-box" style="max-width:720px;margin:20px auto;font-family:Arial,sans-serif;color:#333;">

      <!-- Header -->
      <div style="background:linear-gradient(135deg,#d84315,#bf360c);color:#fff;padding:16px 20px;border-radius:12px 12px 0 0;">
        <div style="font-size:20px;font-weight:bold;margin-bottom:4px;">
          உபகிரக நேர அட்டவணை
        </div>
        <div style="font-size:14px;opacity:0.9;">
          <?php echo esc_html($weekday_tamil); ?> | <?php echo esc_html($date); ?> |
          உதயம்: <?php echo esc_html($sunrise); ?> | அஸ்தமனம்: <?php echo esc_html($sunset); ?>
        </div>
      </div>

      <!-- Time-based upagrahas table -->
      <div style="background:#fff;border:1px solid #eee;border-top:none;border-radius:0 0 0 0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#fbe9e7;">
              <th style="padding:10px 12px;text-align:left;font-size:13px;color:#d84315;border-bottom:2px solid #ffccbc;">உபகிரகம்</th>
              <th style="padding:10px 12px;text-align:center;font-size:13px;color:#d84315;border-bottom:2px solid #ffccbc;">ஆரம்பம்</th>
              <th style="padding:10px 12px;text-align:center;font-size:13px;color:#d84315;border-bottom:2px solid #ffccbc;">முடிவு</th>
              <th style="padding:10px 12px;text-align:center;font-size:13px;color:#d84315;border-bottom:2px solid #ffccbc;">ராசி</th>
              <th style="padding:10px 12px;text-align:center;font-size:13px;color:#d84315;border-bottom:2px solid #ffccbc;">தன்மை</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $row_bg = array('#fff','#fff9f8');
            $ri = 0;

            // Check current time highlight
            $now_mins = intval(date('H')) * 60 + intval(date('i'));

            foreach ($timeTable as $upa):
                $start_parts = explode(':', $upa['startTime']);
                $end_parts   = explode(':', $upa['endTime']);
                $start_mins  = intval($start_parts[0]) * 60 + intval($start_parts[1]);
                $end_mins    = intval($end_parts[0])   * 60 + intval($end_parts[1]);
                $is_now      = ($now_mins >= $start_mins && $now_mins < $end_mins);
                $bg          = $is_now ? '#fff3e0' : $row_bg[$ri % 2];
                $rasi_name   = isset($rasi_names[$upa['rasi']]) ? $rasi_names[$upa['rasi']] : '-';
                $ri++;
            ?>
            <tr style="background:<?php echo $bg; ?>;<?php echo $is_now ? 'font-weight:bold;' : ''; ?>">
              <td style="padding:10px 12px;border-bottom:1px solid #f5f5f5;">
                <?php echo $is_now ? '▶ ' : ''; ?>
                <?php echo esc_html($upa['icon'] ?? ''); ?>
                <?php echo esc_html($upa['name']); ?>
                <?php if ($is_now): ?>
                  <span style="font-size:11px;background:#ff6d00;color:#fff;padding:1px 6px;border-radius:10px;margin-left:4px;">இப்போது</span>
                <?php endif; ?>
              </td>
              <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f5f5f5;font-size:15px;"><?php echo esc_html($upa['startTime']); ?></td>
              <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f5f5f5;font-size:15px;"><?php echo esc_html($upa['endTime']); ?></td>
              <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f5f5f5;font-size:13px;"><?php echo esc_html($rasi_name); ?></td>
              <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f5f5f5;">
                <span style="font-size:12px;padding:3px 8px;border-radius:10px;
                  background:<?php echo $upa['nature'] === 'தீயது' ? '#ffebee' : ($upa['nature'] === 'மிதமானது' ? '#fff8e1' : '#e8f5e9'); ?>;
                  color:<?php echo $upa['nature'] === 'தீயது' ? '#c62828' : ($upa['nature'] === 'மிதமானது' ? '#e65100' : '#2e7d32'); ?>;">
                  <?php echo esc_html($upa['advice'] ?? $upa['nature']); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Dhoomadi section -->
      <?php if (!empty($dhoomadi)): ?>
      <div style="margin-top:12px;background:#fff;border:1px solid #eee;border-radius:0 0 12px 12px;overflow:hidden;">
        <div style="background:#4a148c;color:#fff;padding:10px 16px;font-size:14px;font-weight:bold;">
          தூமாதி உபகிரகங்கள் (ராசி நிலை)
        </div>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#f3e5f5;">
              <th style="padding:8px 12px;text-align:left;font-size:12px;color:#6a1b9a;border-bottom:1px solid #e1bee7;">உபகிரகம்</th>
              <th style="padding:8px 12px;text-align:center;font-size:12px;color:#6a1b9a;border-bottom:1px solid #e1bee7;">ராசி</th>
              <th style="padding:8px 12px;text-align:center;font-size:12px;color:#6a1b9a;border-bottom:1px solid #e1bee7;">நீளம் (°)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dhoomadi as $i => $d):
                $bg = $i % 2 === 0 ? '#fff' : '#faf5ff';
                $rasi_name = isset($rasi_names[$d['rasi']]) ? $rasi_names[$d['rasi']] : '-';
            ?>
            <tr style="background:<?php echo $bg; ?>;">
              <td style="padding:8px 12px;border-bottom:1px solid #f5f5f5;font-size:13px;"><?php echo esc_html($d['name']); ?></td>
              <td style="padding:8px 12px;text-align:center;border-bottom:1px solid #f5f5f5;font-size:13px;"><?php echo esc_html($rasi_name); ?></td>
              <td style="padding:8px 12px;text-align:center;border-bottom:1px solid #f5f5f5;font-size:13px;"><?php echo esc_html(number_format($d['longitude'], 2)); ?>°</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- Footer -->
      <div style="text-align:center;padding:8px;font-size:11px;color:#999;margin-top:4px;">
        கிருஷ்ணாலயா ஜோதிட மையம் | Swiss Ephemeris கணக்கீடு | வேளூர் அட்சரேகை
      </div>
    </div>
    <?php
    return ob_get_clean();
}
