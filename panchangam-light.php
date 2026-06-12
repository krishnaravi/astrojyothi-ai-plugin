<?php
/**
 * AstroJyothi Today's Panchang - Premium Tamil Edition
 * Shortcode: [astrojyothi_panchangam]
 */
if (!defined('ABSPATH')) { exit; }

add_shortcode('astrojyothi_panchangam', 'ajai_render_panchangam_premium');

function ajai_render_panchangam_premium($atts) {
    // 1. அட்சரேகை மற்றும் தீர்க்கரேகை இயல்புநிலை மதிப்புகள் (Vellore)
    $atts = shortcode_atts(array(
        'lat' => '12.9165',
        'lng' => '79.1325'
    ), $atts);

    // 2. தேதி மற்றும் இருப்பிடத் தரவைச் சுத்தம் செய்தல் (Sanitization)
    $date = isset($_GET['pdate']) ? sanitize_text_field($_GET['pdate']) : current_time('Y-m-d');
    $lat  = floatval($atts['lat']);
    $lng  = floatval($atts['lng']);

    // 3. API மூலமாகத் தரவைப் பெறுதல்
    $response = wp_remote_get(
        "http://127.0.0.1:3100/api/panchangam/full?lat={$lat}&lng={$lng}&date={$date}",
        array('timeout' => 20)
    );

    if (is_wp_error($response)) {
        return '<p style="color:red; text-align:center; padding:20px;">தகவல் கிடைக்கவில்லை. இணைய இணைப்பைச் சரிபார்க்கவும்.</p>';
    }

    $d = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($d['success'])) {
        return '<p style="color:red; text-align:center; padding:20px;">பிழை: ' . esc_html($d['error'] ?? 'தரவு இல்லை') . '</p>';
    }

    $p   = $d['panchang'] ?? [];
    $dow = date('w', strtotime($date));
    $weekdays = ['ஞாயிறு','திங்கள்','செவ்வாய்','புதன்','வியாழன்','வெள்ளி','சனி'];
    $wday = $weekdays[$dow] ?? '';
    
    $RASI = ['','மேஷம்','ரிஷபம்','மிதுனம்','கடகம்','சிம்மம்','கன்னி','துலாம்','விருச்சிகம்','தனுசு','மகரம்','கும்பம்','மீனம்'];
    
    // இந்திய நேரத்தின்படி தற்போதைய நிமிடங்களைக் கணக்கிடுதல்
    $current_timestamp = current_time('timestamp');
    $now_mins = (intval(date('H', $current_timestamp)) * 60) + intval(date('i', $current_timestamp));
    
    $prev = date('Y-m-d', strtotime($date . ' -1 day'));
    $next = date('Y-m-d', strtotime($date . ' +1 day'));
    
    // சூரிய மற்றும் சந்திர ராசி கணக்கீடு (பாதுகாப்பானது)
    $sun_idx  = min(12, max(1, intval(($p['sunLongitude'] ?? 0) / 30) + 1));
    $moon_idx = min(12, max(1, intval(($p['moonLongitude'] ?? 0) / 30) + 1));
    $sunRasi  = $RASI[$sun_idx] ?? '';
    $moonRasi = $RASI[$moon_idx] ?? '';

    ob_start();
    ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Noto+Sans+Tamil:wght@300;400;500;600&display=swap');
#prem-root{
  --navy:    #1A3A5C;
  --green:  #1B5E20;
  --teal:   #006064;
  --bg:      #EAF4FB;
  --bg2:     #F0FAF0;
  --card:    #FFFFFF;
  --border: #B0D4E8;
  --border2:#A5D6A7;
  --text:    #1A1A2E;
  --muted:  #546E7A;
  --gold:    #E65100;
  --bad:     #C62828;
  --good:    #1B5E20;
  --mid:     #E65100;
  --bad-bg: #FFEBEE;
  --good-bg:#E8F5E9;
  --mid-bg: #FFF3E0;
  --now-bg: #FFF9C4;
  --head-bg:linear-gradient(135deg,#1A3A5C 0%,#006064 60%,#1B5E20 100%);
  font-family:'Noto Sans Tamil',Arial,sans-serif;
  background:var(--bg);
  color:var(--text);
  max-width:960px;
  margin:0 auto;
  border-radius:16px;
  overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,0.12);
  border:1px solid var(--border);
}
/* HERO */
.prem-hero{
  background:var(--head-bg);
  color:#fff;padding:28px 24px 20px;
  border-bottom:3px solid #A5D6A7;
  position:relative;overflow:hidden;
}
.prem-hero::before{
  content:'ॐ';font-size:180px;color:rgba(255,255,255,0.05);
  position:absolute;right:-10px;top:-30px;font-family:serif;line-height:1;
}
.prem-hero-top{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;}
.prem-eyebrow{font-family:'Cinzel',serif;font-size:10px;letter-spacing:4px;color:#A5D6A7;text-transform:uppercase;margin-bottom:5px;}
.prem-main-title{font-family:'Cinzel',serif;font-size:26px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:3px;}
.prem-sub{font-size:12px;color:rgba(255,255,255,0.75);}
.prem-date-badge{
  background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);
  border:1px solid rgba(255,255,255,0.3);
  border-radius:10px;padding:10px 16px;text-align:right;min-width:140px;
}
.prem-date-big{font-family:'Cinzel',serif;font-size:20px;color:#A5D6A7;font-weight:700;}
.prem-date-small{font-size:12px;color:rgba(255,255,255,0.8);margin-top:2px;}
.prem-sun-row{
  display:flex;gap:18px;margin-top:16px;padding-top:14px;
  border-top:1px solid rgba(255,255,255,0.2);
  font-size:12px;color:rgba(255,255,255,0.8);flex-wrap:wrap;
}
.prem-sun-row span{color:#A5D6A7;font-weight:600;margin-left:5px;}

/* NAV */
.prem-nav{
  background:#E3F2FD;border-bottom:2px solid var(--border);
  padding:10px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;
}
.prem-nav a{
  color:var(--navy);background:#fff;border:1px solid var(--border);
  padding:5px 14px;border-radius:20px;text-decoration:none;font-size:12px;
  font-weight:500;transition:background 0.2s;
}
.prem-nav a:hover{background:#BBDEFB;}
.prem-nav input[type=date]{
  background:#fff;border:1px solid var(--border);
  color:var(--text);padding:5px 10px;border-radius:8px;font-size:12px;font-family:inherit;
}
.prem-nav button{
  background:var(--navy);color:#fff;border:none;
  padding:5px 14px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;font-family:inherit;
}

/* PANCHANG 5 CARDS */
.prem-grid5{display:grid;grid-template-columns:repeat(5,1fr);border-bottom:2px solid var(--border);}
@media(max-width:700px){.prem-grid5{grid-template-columns:repeat(2,1fr);}}
.prem-pancard{
  padding:14px 12px;border-right:1px solid var(--border);
  background:var(--card);
}
.prem-pancard:nth-child(odd){background:#F8FFFE;}
.prem-pancard:last-child{border-right:none;}
.prem-pc-label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;margin-bottom:5px;font-weight:600;}
.prem-pc-val{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:3px;}
.prem-pc-sub{font-size:11px;color:var(--muted);}
.prem-badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;margin-top:4px;border:1px solid transparent;}
.prem-bad{background:var(--bad-bg);color:var(--bad);border-color:#FFCDD2;}
.prem-good{background:var(--good-bg);color:var(--good);border-color:#C8E6C9;}
.prem-mid{background:var(--mid-bg);color:var(--mid);border-color:#FFE0B2;}

/* SPECIALS */
.prem-specials{display:flex;border-bottom:2px solid var(--border);}
.prem-spec-item{flex:1;padding:12px 14px;border-right:1px solid var(--border);text-align:center;background:#F0FFF4;}
.prem-spec-item:nth-child(even){background:#F0F8FF;}
.prem-spec-item:last-child{border-right:none;}
.prem-spec-label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;margin-bottom:3px;font-weight:600;}
.prem-spec-val{font-size:14px;font-weight:700;color:var(--navy);}
.prem-spec-sub{font-size:11px;color:var(--muted);margin-top:2px;}

/* SECTION HEAD */
.prem-section-head{
  padding:10px 18px;background:linear-gradient(90deg,#E3F2FD,#E8F5E9);
  border-bottom:1px solid var(--border);border-top:2px solid var(--border);
  display:flex;align-items:center;gap:8px;
}
.prem-section-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);}
.prem-section-title{font-size:11px;letter-spacing:3px;color:var(--teal);text-transform:uppercase;font-weight:700;}

/* TABLES */
.prem-tbl{width:100%;border-collapse:collapse;background:#fff;}
.prem-tbl th{
  padding:10px 14px;font-size:10px;letter-spacing:2px;color:var(--navy);
  text-transform:uppercase;font-weight:700;border-bottom:2px solid var(--border);
  text-align:left;background:#E3F2FD;
}
.prem-tbl th.tc,.prem-tbl td.tc{text-align:center;}
.prem-tbl td{
  padding:11px 14px;border-bottom:1px solid #E0EEF8;
  font-size:13px;color:var(--text);font-weight:500;
}
.prem-tbl tr:nth-child(even) td{background:#F8FCFF;}
.prem-tbl tr:last-child td{border-bottom:none;}
.prem-tbl tr.prem-now-row td{background:var(--now-bg) !important;}
.prem-tbl tr:hover td{background:#EBF5FB;}
.prem-now-pill{
  display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;
  background:var(--navy);color:#fff;font-weight:700;margin-left:6px;
}
.prem-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px;vertical-align:middle;}

/* HORA GRID */
.prem-hora-grid{
  display:grid;grid-template-columns:repeat(6,1fr);
  background:#fff;border-top:1px solid var(--border);
}
@media(max-width:600px){.prem-hora-grid{grid-template-columns:repeat(3,1fr);}}
.prem-hora-cell{
  border-right:1px solid #E0EEF8;border-bottom:1px solid #E0EEF8;
  padding:8px 6px;text-align:center;background:#fff;
}
.prem-hora-cell:nth-child(even){background:#F8FCFF;}
.prem-hora-cell.prem-hora-now{background:var(--now-bg) !important;font-weight:700;}
.prem-hora-num{font-size:9px;color:var(--muted);margin-bottom:2px;}
.prem-hora-lord{font-size:12px;font-weight:600;color:var(--navy);}
.prem-hora-time{font-size:9px;color:var(--muted);margin-top:2px;}

/* DHOOMADI */
.prem-dhoom-grid{display:grid;grid-template-columns:repeat(5,1fr);background:#fff;}
@media(max-width:600px){.prem-dhoom-grid{grid-template-columns:repeat(2,1fr);}}
.prem-dhoom-cell{padding:14px 10px;text-align:center;border-right:1px solid var(--border2);background:#F0FFF4;}
.prem-dhoom-cell:nth-child(even){background:#F0F8FF;}
.prem-dhoom-cell:last-child{border-right:none;}
.prem-dhoom-name{font-size:13px;font-weight:700;color:var(--teal);margin-bottom:3px;}
.prem-dhoom-rasi{font-size:12px;color:var(--text);font-weight:500;}
.prem-dhoom-deg{font-size:11px;color:var(--muted);margin-top:2px;}

/* FOOTER */
.prem-footer{
  padding:12px 20px;text-align:center;font-size:11px;color:var(--muted);
  background:linear-gradient(90deg,#E3F2FD,#E8F5E9);
  border-top:2px solid var(--border);letter-spacing:1px;
}
.prem-footer b{color:var(--navy);}
</style>

<div id="prem-root">

<div class="prem-hero">
  <div class="prem-hero-top">
    <div>
      <div class="prem-eyebrow">Krishnalaya Astro Centre</div>
      <div class="prem-main-title">Today's Panchang</div>
      <div class="prem-sub">வேளூர் | Vellore, Tamil Nadu | Swiss Ephemeris</div>
    </div>
    <div class="prem-date-badge">
      <div class="prem-date-big"><?php echo esc_html($wday); ?></div>
      <div class="prem-date-small"><?php echo esc_html(date('d M Y', strtotime($date))); ?></div>
    </div>
  </div>
  <div class="prem-sun-row">
    <div>☀️ உதயம்<span><?php echo esc_html($p['sunrise'] ?? '-'); ?></span></div>
    <div>🌅 அஸ்தமனம்<span><?php echo esc_html($p['sunset'] ?? '-'); ?></span></div>
    <div>⏱ நாள் கால அளவு<span><?php echo esc_html($p['dayDuration'] ?? '-'); ?></span></div>
    <div>☀️ சூரியன்<span><?php echo esc_html($sunRasi); ?> <?php echo esc_html(number_format(floatval($p['sunLongitude'] ?? 0), 1)); ?>°</span></div>
    <div>🌙 சந்திரன்<span><?php echo esc_html($moonRasi); ?> <?php echo esc_html(number_format(floatval($p['moonLongitude'] ?? 0), 1)); ?>°</span></div>
  </div>
</div>

<form class="prem-nav" method="get">
  <a href="<?php echo esc_url(add_query_arg('pdate', $prev)); ?>">← முந்தைய நாள்</a>
  <input type="date" name="pdate" value="<?php echo esc_attr($date); ?>">
  <button type="submit">செல்க</button>
  <a href="<?php echo esc_url(add_query_arg('pdate', $next)); ?>">அடுத்த நாள் →</a>
  <a href="<?php echo esc_url(remove_query_arg('pdate')); ?>" style="margin-left:auto;">இன்று</a>
</form>

<div class="prem-grid5">
  <div class="prem-pancard">
    <div class="prem-pc-label">வாரம்</div>
    <div class="prem-pc-val"><?php echo esc_html($p['vaaram']['name'] ?? '-'); ?></div>
    <div class="prem-pc-sub"><?php echo esc_html($p['vaaram']['tamil_lord'] ?? '-'); ?> அதிபதி</div>
    <span class="prem-badge prem-mid"><?php echo esc_html($p['vaaram']['suitable'] ?? '-'); ?></span>
  </div>
  <div class="prem-pancard">
    <div class="prem-pc-label">திதி</div>
    <div class="prem-pc-val"><?php echo esc_html($p['thithi']['name'] ?? '-'); ?></div>
    <div class="prem-pc-sub"><?php echo esc_html($p['thithi']['paksha'] ?? '-'); ?> | <?php echo esc_html(number_format(floatval($p['thithi']['percentElapsed'] ?? 0), 1)); ?>%</div>
    <span class="prem-badge prem-good"><?php echo esc_html($p['thithi']['deity'] ?? '-'); ?></span>
  </div>
  <div class="prem-pancard">
    <div class="prem-pc-label">நட்சத்திரம்</div>
    <div class="prem-pc-val"><?php echo esc_html($p['nakshatra']['name'] ?? '-'); ?></div>
    <div class="prem-pc-sub"><?php echo esc_html($p['nakshatra']['pada'] ?? '-'); ?>-ம் பாதம் | <?php echo esc_html($p['nakshatra']['lord'] ?? '-'); ?></div>
    <span class="prem-badge prem-mid"><?php echo esc_html($p['nakshatra']['gana'] ?? '-'); ?> கணம்</span>
  </div>
  <div class="prem-pancard">
    <div class="prem-pc-label">யோகம்</div>
    <div class="prem-pc-val"><?php echo esc_html($p['yoga']['name'] ?? '-'); ?></div>
    <div class="prem-pc-sub"><?php echo esc_html($p['yoga']['deity'] ?? '-'); ?> | <?php echo esc_html(number_format(floatval($p['yoga']['degreeElapsed'] ?? 0), 1)); ?>°</div>
    <span class="prem-badge <?php echo ($p['yoga']['nature'] ?? '') === 'தீயது' ? 'prem-bad' : 'prem-good'; ?>">
      <?php echo esc_html($p['yoga']['nature'] ?? '-'); ?>
    </span>
  </div>
  <div class="prem-pancard">
    <div class="prem-pc-label">கரணம்</div>
    <div class="prem-pc-val"><?php echo esc_html($p['karana']['name'] ?? '-'); ?></div>
    <div class="prem-pc-sub"><?php echo esc_html($p['karana']['type'] ?? '-'); ?> | <?php echo esc_html($p['karana']['deity'] ?? '-'); ?></div>
    <span class="prem-badge <?php echo ($p['karana']['nature'] ?? '') === 'நல்லது' ? 'prem-good' : 'prem-mid'; ?>">
      <?php echo esc_html($p['karana']['nature'] ?? '-'); ?>
    </span>
  </div>
</div>

<div class="prem-specials">
  <div class="prem-spec-item">
    <div class="prem-spec-label">வாரசூலை</div>
    <div class="prem-spec-val"><?php echo esc_html($d['varasoolai']['tamil'] ?? '-'); ?> திசை</div>
    <div class="prem-spec-sub" style="color:var(--bad);font-weight:600;">பயணம் தவிர்க்கவும்</div>
  </div>
  <div class="prem-spec-item">
    <div class="prem-spec-label">நேத்திரம்</div>
    <div class="prem-spec-val"><?php echo esc_html($d['nethram']['tamil'] ?? '-'); ?></div>
    <div class="prem-spec-sub">கண் கிரகம்</div>
  </div>
  <div class="prem-spec-item">
    <div class="prem-spec-label">ஜீவன்</div>
    <div class="prem-spec-val"><?php echo esc_html($d['jeevan']['tamil'] ?? '-'); ?></div>
    <div class="prem-spec-sub">உயிர் கிரகம்</div>
  </div>
  <div class="prem-spec-item">
    <div class="prem-spec-label">சூலை திசை</div>
    <div class="prem-spec-val"><?php echo esc_html($p['vaaram']['sulai'] ?? '-'); ?></div>
    <div class="prem-spec-sub">வார சூல திசை</div>
  </div>
</div>

<div class="prem-section-head">
  <span class="prem-section-dot"></span>
  <span class="prem-section-title">உபகிரக நேர அட்டவணை</span>
</div>
<table class="prem-tbl">
  <thead>
    <tr>
      <th>உபகிரகம்</th>
      <th class="tc" style="width:90px;">ஆரம்பம்</th>
      <th class="tc" style="width:90px;">முடிவு</th>
      <th class="tc" style="width:110px;">ராசி</th>
      <th class="tc">தன்மை</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!empty($d['upagrahas']) && is_array($d['upagrahas'])): ?>
    <?php foreach ($d['upagrahas'] as $u):
      $sp = explode(':', $u['startTime'] ?? '0:0'); 
      $ep = explode(':', $u['endTime'] ?? '0:0');
      $sm = (intval($sp[0] ?? 0) * 60) + intval($sp[1] ?? 0); 
      $em = (intval($ep[0] ?? 0) * 60) + intval($ep[1] ?? 0);
      $isNow = ($now_mins >= $sm && $now_mins < $em);
    ?>
    <tr class="<?php echo $isNow ? 'prem-now-row' : ''; ?>">
      <td style="font-weight:600;">
        <?php echo esc_html(($u['icon'] ?? '') . ' ' . ($u['name'] ?? '')); ?>
        <?php if ($isNow): ?><span class="prem-now-pill">NOW</span><?php endif; ?>
      </td>
      <td class="tc" style="font-weight:600;"><?php echo esc_html($u['startTime'] ?? '-'); ?></td>
      <td class="tc" style="font-weight:600;"><?php echo esc_html($u['endTime'] ?? '-'); ?></td>
      <td class="tc" style="color:var(--muted);"><?php echo esc_html($u['rasi'] ?? '-'); ?></td>
      <td class="tc">
        <span class="prem-badge <?php echo ($u['nature'] ?? '') === 'தீயது' ? 'prem-bad' : (($u['nature'] ?? '') === 'மிதமானது' ? 'prem-mid' : 'prem-good'); ?>">
          <?php echo esc_html($u['advice'] ?? '-'); ?>
        </span>
      </td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

<div class="prem-section-head">
  <span class="prem-section-dot"></span>
  <span class="prem-section-title">கௌரி பஞ்சாங்கம்</span>
</div>
<table class="prem-tbl">
  <thead>
    <tr>
      <th style="width:40px;">#</th><th>கௌரி</th>
      <th class="tc">ஆரம்பம்</th><th class="tc">முடிவு</th>
      <th class="tc">தன்மை</th><th>பொருத்தம்</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!empty($d['gauri']) && is_array($d['gauri'])): ?>
    <?php foreach ($d['gauri'] as $g):
      $sp = explode(':', $g['startTime'] ?? '0:0'); 
      $ep = explode(':', $g['endTime'] ?? '0:0');
      $sm = (intval($sp[0] ?? 0) * 60) + intval($sp[1] ?? 0); 
      $em = (intval($ep[0] ?? 0) * 60) + intval($ep[1] ?? 0);
      $isNow = ($now_mins >= $sm && $now_mins < $em);
    ?>
    <tr class="<?php echo $isNow ? 'prem-now-row' : ''; ?>">
      <td style="color:var(--muted);font-size:12px;"><?php echo esc_html($g['part'] ?? '-'); ?></td>
      <td style="font-weight:600;">
        <?php echo esc_html($g['name'] ?? '-'); ?>
        <?php if ($isNow): ?><span class="prem-now-pill">NOW</span><?php endif; ?>
      </td>
      <td class="tc" style="font-weight:600;"><?php echo esc_html($g['startTime'] ?? '-'); ?></td>
      <td class="tc" style="font-weight:600;"><?php echo esc_html($g['endTime'] ?? '-'); ?></td>
      <td class="tc">
        <span class="prem-badge <?php echo ($g['nature'] ?? '') === 'அசுபம்' ? 'prem-bad' : (($g['nature'] ?? '') === 'சுபம்' ? 'prem-good' : 'prem-mid'); ?>">
          <?php echo esc_html($g['nature'] ?? '-'); ?>
        </span>
      </td>
      <td style="font-size:12px;color:var(--muted);"><?php echo esc_html($g['suitable'] ?? '-'); ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

<div class="prem-section-head">
  <span class="prem-section-dot"></span>
  <span class="prem-section-title">ஓரை நேரம் · Hora</span>
</div>
<div class="prem-hora-grid">
  <?php if (!empty($d['hora']) && is_array($d['hora'])): ?>
    <?php foreach ($d['hora'] as $h):
      $sp = explode(':', $h['startTime'] ?? '0:0'); 
      $ep = explode(':', $h['endTime'] ?? '0:0');
      $sm = (intval($sp[0] ?? 0) * 60) + intval($sp[1] ?? 0); 
      $em = (intval($ep[0] ?? 0) * 60) + intval($ep[1] ?? 0);
      $isNow = ($now_mins >= $sm && $now_mins < $em);
    ?>
    <div class="prem-hora-cell <?php echo $isNow ? 'prem-hora-now' : ''; ?>">
      <div class="prem-hora-num"><?php echo esc_html($h['hora'] ?? '-'); ?></div>
      <div class="prem-hora-lord">
        <span class="prem-dot" style="background:<?php echo esc_attr($h['color'] ?? '#ccc'); ?>;"></span>
        <?php echo esc_html($h['name'] ?? '-'); ?>
        <?php if ($isNow): echo ' ◀'; endif; ?>
      </div>
      <div class="prem-hora-time"><?php echo esc_html($h['startTime'] ?? '-'); ?>–<?php echo esc_html($h['endTime'] ?? '-'); ?></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="prem-section-head">
  <span class="prem-section-dot"></span>
  <span class="prem-section-title">தூமாதி உபகிரகங்கள்</span>
</div>
<div class="prem-dhoom-grid">
  <?php if (!empty($d['dhoomadi']) && is_array($d['dhoomadi'])): ?>
    <?php foreach ($d['dhoomadi'] as $dm): ?>
    <div class="prem-dhoom-cell">
      <div class="prem-dhoom-name"><?php echo esc_html($dm['name'] ?? '-'); ?></div>
      <div class="prem-dhoom-rasi"><?php echo esc_html($dm['rasi'] ?? '-'); ?></div>
      <div class="prem-dhoom-deg"><?php echo esc_html(number_format(floatval($dm['longitude'] ?? 0), 1)); ?>°</div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="prem-footer">
  <b>Krishnalaya Astro Centre</b> · Swiss Ephemeris Precision ·
  Vellore (12.9165°N, 79.1325°E) · <?php echo esc_html(date('h:i A', $current_timestamp)); ?> IST
</div>

</div>
    <?php
    return ob_get_clean();
}