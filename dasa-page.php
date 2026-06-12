<?php
/**
 * AstroJyothi Vimshottari Dasa - PHP Expand Edition
 * Shortcode: [astrojyothi_dasa]
 */
if (!defined('ABSPATH')) { exit; }

add_shortcode('astrojyothi_dasa', 'ajai_render_dasa');

function ajai_render_dasa($atts) {
    $atts = shortcode_atts(array('lat'=>'12.9165','lng'=>'79.1325'), $atts);

    $year   = isset($_GET['by'])  ? intval($_GET['by'])   : '';
    $month  = isset($_GET['bm'])  ? intval($_GET['bm'])   : '';
    $day    = isset($_GET['bd'])  ? intval($_GET['bd'])   : '';
    $hours  = isset($_GET['bh'])  ? floatval($_GET['bh']) : '';
    $lat    = isset($_GET['lat']) ? floatval($_GET['lat']): floatval($atts['lat']);
    $lng    = isset($_GET['lng']) ? floatval($_GET['lng']): floatval($atts['lng']);
    $level  = isset($_GET['lv'])  ? intval($_GET['lv'])   : 3;

    // Expand state from URL
    $exp_dasa   = isset($_GET['xd'])  ? sanitize_text_field($_GET['xd'])  : '';
    $exp_bhukti = isset($_GET['xb'])  ? sanitize_text_field($_GET['xb'])  : '';
    $exp_antara = isset($_GET['xa'])  ? sanitize_text_field($_GET['xa'])  : '';
    $exp_sukshma= isset($_GET['xs'])  ? sanitize_text_field($_GET['xs'])  : '';

    $data = null; $error = '';
    $sukshma_data = null; $prana_data = null;

    if ($year && $month && $day && $hours !== '') {
        $url = "http://127.0.0.1:3100/api/dasa?year={$year}&month={$month}&day={$day}&hours={$hours}&lat={$lat}&lng={$lng}&level=3";
        $response = wp_remote_get($url, array('timeout'=>30));
        if (is_wp_error($response)) { $error = 'API error'; }
        else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['success'])) { $error = $data['error']??'error'; $data=null; }
        }

        // Load sukshma if antara expanded
        if ($data && $exp_dasa && $exp_bhukti && $exp_antara) {
            $surl = "http://127.0.0.1:3100/api/dasa/detail?year={$year}&month={$month}&day={$day}&hours={$hours}&dasa={$exp_dasa}&bhukti={$exp_bhukti}&antara={$exp_antara}";
            $sr = wp_remote_get($surl, array('timeout'=>20));
            if (!is_wp_error($sr)) {
                $sd = json_decode(wp_remote_retrieve_body($sr), true);
                if (!empty($sd['success'])) $sukshma_data = $sd['data'];
            }
        }

        // Load prana if sukshma expanded
        if ($data && $exp_dasa && $exp_bhukti && $exp_antara && $exp_sukshma) {
            $purl = "http://127.0.0.1:3100/api/dasa/detail?year={$year}&month={$month}&day={$day}&hours={$hours}&dasa={$exp_dasa}&bhukti={$exp_bhukti}&antara={$exp_antara}&sukshma={$exp_sukshma}";
            $pr = wp_remote_get($purl, array('timeout'=>20));
            if (!is_wp_error($pr)) {
                $pd = json_decode(wp_remote_retrieve_body($pr), true);
                if (!empty($pd['success'])) $prana_data = $pd['data'];
            }
        }
    }

    $COLORS = array(
        'Sun'=>'#FF6B35','Moon'=>'#4A90D9','Mars'=>'#E63946',
        'Mercury'=>'#2DC653','Jupiter'=>'#FFD166','Venus'=>'#F4A261',
        'Saturn'=>'#6D6875','Rahu'=>'#7B2D8B','Ketu'=>'#8B4513'
    );
    $ICONS = array(
        'Sun'=>'☀️','Moon'=>'🌙','Mars'=>'🔴','Mercury'=>'💚',
        'Jupiter'=>'🟡','Venus'=>'⭐','Saturn'=>'🪐','Rahu'=>'🐉','Ketu'=>'☄️'
    );

    $today = date('Y-m-d');
    $page_url = get_permalink();

    function dasa_now($s,$e,$t){ return $s<=$t && $t<=$e; }
    function dasa_left($end,$today){
        $diff=(strtotime($end)-strtotime($today))/86400;
        if($diff<0)return '';
        if($diff<365)return round($diff).' நாட்கள்';
        return round($diff/365.25,1).' ஆண்டுகள்';
    }
    function dasa_url($base, $params){
        return add_query_arg($params, $base);
    }

    ob_start();
    ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Noto+Sans+Tamil:wght@300;400;500;600&display=swap');
#dasa-root{
  --navy:#1A3A5C;--teal:#006064;--green:#1B5E20;
  --bg:#EAF4FB;--card:#fff;--border:#B0D4E8;
  --text:#1A1A2E;--muted:#546E7A;
  --now-bg:#FFFDE7;--now-border:#F9A825;
  font-family:'Noto Sans Tamil',Arial,sans-serif;
  background:var(--bg);color:var(--text);
  max-width:1000px;margin:0 auto;
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,0.12);
  border:1px solid var(--border);
}
.dasa-hero{background:linear-gradient(135deg,#1A3A5C 0%,#006064 60%,#1B5E20 100%);color:#fff;padding:24px 24px 18px;border-bottom:3px solid #A5D6A7;position:relative;overflow:hidden;}
.dasa-hero::before{content:'ॐ';font-size:160px;color:rgba(255,255,255,0.04);position:absolute;right:-10px;top:-30px;font-family:serif;line-height:1;}
.dasa-eyebrow{font-family:'Cinzel',serif;font-size:10px;letter-spacing:4px;color:#A5D6A7;text-transform:uppercase;margin-bottom:4px;}
.dasa-title{font-family:'Cinzel',serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:3px;}
.dasa-sub{font-size:12px;color:rgba(255,255,255,0.75);}
.dasa-form{background:#E3F2FD;border-bottom:2px solid var(--border);padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
.dasa-fg{display:flex;flex-direction:column;gap:4px;position:relative;}
.dasa-fg label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;font-weight:600;}
.dasa-fg input,.dasa-fg select{background:#fff;border:1px solid var(--border);color:var(--text);padding:7px 10px;border-radius:8px;font-size:13px;font-family:inherit;}
.dasa-fg input[name=by]{width:70px;}
.dasa-fg input[name=bm],.dasa-fg input[name=bd]{width:55px;}
.dasa-time-row{display:flex;gap:4px;align-items:center;}
.dasa-time-row input{width:44px!important;text-align:center;font-weight:600;}
.dasa-time-row select{width:65px!important;}
.dasa-time-sep{font-size:18px;font-weight:700;color:var(--navy);}
.dasa-city{width:150px!important;}
.dasa-latlong{display:flex;gap:6px;}
.dasa-latlong input{width:78px!important;}
.dasa-lv-sel{width:155px!important;}
.dasa-city-sug{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 8px 8px;z-index:100;max-height:160px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
.dasa-city-sug div{padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;}
.dasa-city-sug div:hover{background:#E3F2FD;}
.dasa-btn{background:var(--navy);color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;align-self:flex-end;}
.dasa-btn:hover{background:var(--teal);}
.dasa-current{display:flex;border-bottom:2px solid var(--now-border);background:var(--now-bg);flex-wrap:wrap;}
.dasa-cur-item{flex:1;min-width:130px;padding:12px 14px;border-right:1px solid #F9A825;text-align:center;}
.dasa-cur-item:last-child{border-right:none;}
.dasa-cur-label{font-size:10px;letter-spacing:2px;color:#E65100;text-transform:uppercase;margin-bottom:3px;font-weight:600;}
.dasa-cur-val{font-size:15px;font-weight:700;color:var(--navy);}
.dasa-cur-end{font-size:11px;color:var(--muted);margin-top:2px;}
.dasa-now-pill{background:#E65100;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:5px;}
.dasa-sec-head{padding:10px 18px;background:linear-gradient(90deg,#E3F2FD,#E8F5E9);border-bottom:1px solid var(--border);border-top:2px solid var(--border);display:flex;align-items:center;gap:8px;}
.dasa-sec-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);}
.dasa-sec-title{font-size:11px;letter-spacing:3px;color:var(--teal);text-transform:uppercase;font-weight:700;}
/* TREE */
.dasa-tree{background:#fff;}
.dasa-maha{border-bottom:1px solid #E0EEF8;}
.dasa-row{display:flex;align-items:center;gap:10px;padding:11px 16px;cursor:pointer;text-decoration:none;color:inherit;transition:background 0.15s;}
.dasa-row:hover{background:#F0F8FF;}
.dasa-row.now{background:var(--now-bg);border-left:4px solid var(--now-border);}
.dasa-row.active{background:#E8F5E9;border-left:4px solid #2E7D32;}
.pdot{border-radius:50%;display:inline-block;flex-shrink:0;}
.dasa-planet{font-weight:700;color:var(--navy);}
.dasa-dates{font-size:12px;color:var(--muted);flex:1;}
.dasa-yrs{font-size:12px;color:var(--teal);font-weight:600;min-width:65px;text-align:right;}
.dasa-arr{font-size:11px;color:var(--muted);margin-left:4px;}
/* Bhukti */
.bhukti-wrap{display:none;background:#FAFEFF;}
.bhukti-wrap.open{display:block;}
.bhukti-row{display:flex;align-items:center;gap:9px;padding:9px 12px 9px 32px;cursor:pointer;text-decoration:none;color:inherit;border-bottom:1px solid #EEF5FA;transition:background 0.15s;}
.bhukti-row:hover{background:#F0F8FF;}
.bhukti-row.now{background:var(--now-bg);border-left:3px solid var(--now-border);}
.bhukti-row.active{background:#E8F5E9;border-left:3px solid #2E7D32;}
/* Antara */
.antara-wrap{display:none;background:#F5FBFF;}
.antara-wrap.open{display:block;}
.antara-row{display:flex;align-items:center;gap:8px;padding:8px 10px 8px 48px;cursor:pointer;text-decoration:none;color:inherit;border-bottom:1px solid #EEF5FA;transition:background 0.15s;}
.antara-row:hover{background:#EBF5FB;}
.antara-row.now{background:var(--now-bg);border-left:3px solid var(--now-border);}
.antara-row.active{background:#E8F5E9;border-left:3px solid #2E7D32;}
/* Sukshma */
.sukshma-wrap{background:#F0F9FF;padding:0;}
.sukshma-row{display:flex;align-items:center;gap:8px;padding:7px 10px 7px 62px;cursor:pointer;text-decoration:none;color:inherit;border-bottom:1px solid #EEF5FA;transition:background 0.15s;}
.sukshma-row:hover{background:#EBF5FB;}
.sukshma-row.now{background:var(--now-bg);border-left:3px solid var(--now-border);}
.sukshma-row.active{background:#E8F5E9;border-left:3px solid #2E7D32;}
/* Prana */
.prana-wrap{background:#EBF7FF;padding:0;}
.prana-row{display:flex;align-items:center;gap:7px;padding:6px 10px 6px 74px;border-bottom:1px solid #EEF5FA;color:inherit;}
.prana-row.now{background:var(--now-bg);}
/* Summary */
.dasa-sum-tbl{width:100%;border-collapse:collapse;background:#fff;}
.dasa-sum-tbl th{padding:9px 12px;font-size:10px;letter-spacing:2px;color:var(--navy);text-transform:uppercase;font-weight:700;border-bottom:2px solid var(--border);background:#E3F2FD;text-align:left;}
.dasa-sum-tbl td{padding:9px 12px;border-bottom:1px solid #E0EEF8;font-size:13px;}
.dasa-sum-tbl tr:nth-child(even) td{background:#F8FCFF;}
.dasa-sum-tbl tr.now-row td{background:var(--now-bg);font-weight:600;}
.dasa-footer{padding:12px 20px;text-align:center;font-size:11px;color:var(--muted);background:linear-gradient(90deg,#E3F2FD,#E8F5E9);border-top:2px solid var(--border);}
.dasa-footer b{color:var(--navy);}
.dasa-empty{padding:40px;text-align:center;color:var(--muted);background:#fff;}
.dasa-empty-icon{font-size:48px;margin-bottom:12px;}
.dasa-error{padding:20px;background:#FFEBEE;color:#C62828;border-left:4px solid #C62828;margin:16px;}
</style>

<div id="dasa-root">
<div class="dasa-hero">
  <div class="dasa-eyebrow">Krishnalaya Astro Centre</div>
  <div class="dasa-title">விம்சோத்தரி தசா · Vimshottari Dasa</div>
  <div class="dasa-sub">தசா · புத்தி · அந்தரம் · சூட்சுமம் · பிராணன் | Swiss Ephemeris</div>
</div>

<form class="dasa-form" method="get" id="dasa-form">
  <div class="dasa-fg">
    <label>ஆண்டு</label>
    <input type="number" name="by" placeholder="1985" value="<?php echo esc_attr($year);?>" min="1900" max="2100">
  </div>
  <div class="dasa-fg">
    <label>மாதம்</label>
    <input type="number" name="bm" placeholder="6" value="<?php echo esc_attr($month);?>" min="1" max="12">
  </div>
  <div class="dasa-fg">
    <label>நாள்</label>
    <input type="number" name="bd" placeholder="15" value="<?php echo esc_attr($day);?>" min="1" max="31">
  </div>
  <div class="dasa-fg">
    <label>நேரம் (IST)</label>
    <div class="dasa-time-row">
      <input type="text" id="dhr" placeholder="07" maxlength="2" inputmode="numeric" value="<?php if($hours){$h=floor($hours);$h12=$h%12;if($h12==0)$h12=12;echo $h12;}?>">
      <span class="dasa-time-sep">:</span>
      <input type="text" id="dmn" placeholder="30" maxlength="2" inputmode="numeric" value="<?php if($hours)echo str_pad(round(($hours-floor($hours))*60),2,'0',STR_PAD_LEFT);?>">
      <select id="dap">
        <option value="AM" <?php if($hours&&floor($hours)<12)echo'selected';?>>AM</option>
        <option value="PM" <?php if($hours&&floor($hours)>=12)echo'selected';?>>PM</option>
      </select>
      <input type="hidden" name="bh" id="dhh" value="<?php echo esc_attr($hours);?>">
    </div>
  </div>
  <div class="dasa-fg">
    <label>ஊர் பெயர்</label>
    <input type="text" id="dcity" class="dasa-city" placeholder="நகரம்..." value="<?php echo esc_attr(isset($_GET['city'])?$_GET['city']:'Vellore');?>">
    <input type="hidden" name="city" id="dcity-h" value="<?php echo esc_attr(isset($_GET['city'])?$_GET['city']:'Vellore');?>">
    <div class="dasa-city-sug" id="dsug"></div>
  </div>
  <div class="dasa-fg">
    <label>Lat / Lng</label>
    <div class="dasa-latlong">
      <input type="number" name="lat" id="dlat" placeholder="12.9165" value="<?php echo esc_attr($lat);?>" step="0.0001">
      <input type="number" name="lng" id="dlng" placeholder="79.1325" value="<?php echo esc_attr($lng);?>" step="0.0001">
    </div>
  </div>
  <button type="submit" class="dasa-btn">கணக்கிடு →</button>
</form>

<?php if ($error): ?>
<div class="dasa-error">பிழை: <?php echo esc_html($error);?></div>
<?php elseif (!$data): ?>
<div class="dasa-empty">
  <div class="dasa-empty-icon">🪐</div>
  <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:6px;">பிறந்த விவரங்களை உள்ளிடுங்கள்</div>
  <div style="font-size:13px;">விம்சோத்தரி தசா — 5 நிலைகளில் கணக்கீடு</div>
</div>
<?php else:
  $cur = $data['current'];

  // Build base URL (without expand params)
  $base_params = array('by'=>$year,'bm'=>$month,'bd'=>$day,'bh'=>$hours,'lat'=>$lat,'lng'=>$lng);
  if(isset($_GET['city'])) $base_params['city'] = $_GET['city'];
?>

<!-- CURRENT BAR -->
<div class="dasa-current">
  <?php if($cur['dasa']): $p=$cur['dasa']; ?>
  <div class="dasa-cur-item">
    <div class="dasa-cur-label">இப்போதைய தசா</div>
    <div class="dasa-cur-val"><?php echo esc_html(($ICONS[$p['planet']]??'').' '.$p['tamil']);?> <span class="dasa-now-pill">NOW</span></div>
    <div class="dasa-cur-end">முடிவு: <?php echo esc_html($p['end']);?> (<?php echo esc_html(dasa_left($p['end'],$today));?>)</div>
  </div>
  <?php endif; if($cur['bhukti']): $p=$cur['bhukti']; ?>
  <div class="dasa-cur-item">
    <div class="dasa-cur-label">புத்தி</div>
    <div class="dasa-cur-val"><?php echo esc_html(($ICONS[$p['planet']]??'').' '.$p['tamil']);?></div>
    <div class="dasa-cur-end">முடிவு: <?php echo esc_html($p['end']);?> (<?php echo esc_html(dasa_left($p['end'],$today));?>)</div>
  </div>
  <?php endif; if($cur['antara']): $p=$cur['antara']; ?>
  <div class="dasa-cur-item">
    <div class="dasa-cur-label">அந்தரம்</div>
    <div class="dasa-cur-val"><?php echo esc_html(($ICONS[$p['planet']]??'').' '.$p['tamil']);?></div>
    <div class="dasa-cur-end">முடிவு: <?php echo esc_html($p['end']);?> (<?php echo esc_html(dasa_left($p['end'],$today));?>)</div>
  </div>
  <?php endif; ?>
  <div class="dasa-cur-item">
    <div class="dasa-cur-label">சந்திர நீளம்</div>
    <div class="dasa-cur-val"><?php echo esc_html(number_format($data['moon_longitude'],2));?>°</div>
    <div class="dasa-cur-end"><?php echo esc_html("{$data['birth']['day']}/{$data['birth']['month']}/{$data['birth']['year']}");?></div>
  </div>
</div>

<!-- DASA TREE -->
<div class="dasa-sec-head"><span class="dasa-sec-dot"></span><span class="dasa-sec-title">விம்சோத்தரி தசா அட்டவணை</span></div>
<div class="dasa-tree">
<?php foreach($data['dasas'] as $dasa):
  $isNowD = dasa_now($dasa['start'],$dasa['end'],$today);
  $isExpD = ($exp_dasa === $dasa['planet']);
  $dc = $COLORS[$dasa['planet']]??'#666';
  $di = $ICONS[$dasa['planet']]??'';

  // URL to expand/collapse this dasa
  $toggle_dasa_url = $isExpD
    ? add_query_arg($base_params, $page_url)
    : add_query_arg(array_merge($base_params, array('xd'=>$dasa['planet'])), $page_url);
?>
<div class="dasa-maha">
  <a href="<?php echo esc_url($toggle_dasa_url);?>" class="dasa-row <?php echo $isNowD?'now':''; ?> <?php echo $isExpD?'active':'';?>">
    <span class="pdot" style="background:<?php echo $dc;?>;width:12px;height:12px;"></span>
    <span class="dasa-planet" style="min-width:110px;">
      <?php echo esc_html($di.' '.$dasa['tamil']);?>
      <?php if($isNowD):?><span class="dasa-now-pill">NOW</span><?php endif;?>
    </span>
    <span class="dasa-dates"><?php echo esc_html($dasa['start']);?> → <?php echo esc_html($dasa['end']);?></span>
    <span class="dasa-yrs"><?php echo esc_html($dasa['years']);?> ஆண்டு</span>
    <?php if(!empty($dasa['bhukti'])):?>
    <span class="dasa-arr"><?php echo $isExpD?'▼':'▶';?></span>
    <?php endif;?>
  </a>

  <?php if($isExpD && !empty($dasa['bhukti'])): ?>
  <div class="bhukti-wrap open">
    <?php foreach($dasa['bhukti'] as $bhukti):
      $isNowB = $isNowD && dasa_now($bhukti['start'],$bhukti['end'],$today);
      $isExpB = ($isExpD && $exp_bhukti===$bhukti['planet']);
      $bc=$COLORS[$bhukti['planet']]??'#666';
      $bi=$ICONS[$bhukti['planet']]??'';

      $toggle_bhukti_url = $isExpB
        ? add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'])), $page_url)
        : add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'],'xb'=>$bhukti['planet'])), $page_url);
    ?>
    <a href="<?php echo esc_url($toggle_bhukti_url);?>" class="bhukti-row <?php echo $isNowB?'now':'';?> <?php echo $isExpB?'active':'';?>">
      <span class="pdot" style="background:<?php echo $bc;?>;width:9px;height:9px;"></span>
      <span class="dasa-planet" style="font-size:13px;min-width:100px;">
        <?php echo esc_html($bi.' '.$bhukti['tamil']);?>
        <?php if($isNowB):?><span class="dasa-now-pill" style="font-size:9px;">NOW</span><?php endif;?>
      </span>
      <span class="dasa-dates"><?php echo esc_html($bhukti['start']);?> → <?php echo esc_html($bhukti['end']);?></span>
      <?php if(!empty($bhukti['antara'])):?>
      <span class="dasa-arr"><?php echo $isExpB?'▼':'▶';?></span>
      <?php endif;?>
    </a>

    <?php if($isExpB && !empty($bhukti['antara'])): ?>
    <div class="antara-wrap open">
      <?php foreach($bhukti['antara'] as $antara):
        $isNowA = $isNowB && dasa_now($antara['start'],$antara['end'],$today);
        $isExpA = ($isExpB && $exp_antara===$antara['planet']);
        $ac=$COLORS[$antara['planet']]??'#666';
        $ai=$ICONS[$antara['planet']]??'';

        $toggle_antara_url = $isExpA
          ? add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'],'xb'=>$bhukti['planet'])), $page_url)
          : add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'],'xb'=>$bhukti['planet'],'xa'=>$antara['planet'])), $page_url);
      ?>
      <a href="<?php echo esc_url($toggle_antara_url);?>" class="antara-row <?php echo $isNowA?'now':'';?> <?php echo $isExpA?'active':'';?>">
        <span class="pdot" style="background:<?php echo $ac;?>;width:7px;height:7px;"></span>
        <span class="dasa-planet" style="font-size:12px;min-width:95px;">
          <?php echo esc_html($ai.' '.$antara['tamil']);?>
          <?php if($isNowA):?><span class="dasa-now-pill" style="font-size:8px;">NOW</span><?php endif;?>
        </span>
        <span class="dasa-dates"><?php echo esc_html($antara['start']);?> → <?php echo esc_html($antara['end']);?></span>
        <span class="dasa-arr"><?php echo $isExpA?'▼':'▶';?></span>
      </a>

      <?php if($isExpA && $sukshma_data): ?>
      <div class="sukshma-wrap">
        <?php foreach($sukshma_data as $sukshma):
          $isNowS = $isNowA && dasa_now($sukshma['start'],$sukshma['end'],$today);
          $isExpS = ($isExpA && $exp_sukshma===$sukshma['planet']);
          $sc=$COLORS[$sukshma['planet']]??'#666';
          $si_=$ICONS[$sukshma['planet']]??'';

          $toggle_sukshma_url = $isExpS
            ? add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'],'xb'=>$bhukti['planet'],'xa'=>$antara['planet'])), $page_url)
            : add_query_arg(array_merge($base_params,array('xd'=>$dasa['planet'],'xb'=>$bhukti['planet'],'xa'=>$antara['planet'],'xs'=>$sukshma['planet'])), $page_url);
        ?>
        <a href="<?php echo esc_url($toggle_sukshma_url);?>" class="sukshma-row <?php echo $isNowS?'now':'';?> <?php echo $isExpS?'active':'';?>">
          <span class="pdot" style="background:<?php echo $sc;?>;width:6px;height:6px;"></span>
          <span class="dasa-planet" style="font-size:11px;min-width:90px;">
            <?php echo esc_html($si_.' '.$sukshma['tamil']);?>
            <?php if($isNowS):?><span class="dasa-now-pill" style="font-size:8px;">NOW</span><?php endif;?>
          </span>
          <span class="dasa-dates" style="font-size:11px;"><?php echo esc_html($sukshma['start']);?> → <?php echo esc_html($sukshma['end']);?></span>
          <span class="dasa-arr"><?php echo $isExpS?'▼':'▶';?></span>
        </a>

        <?php if($isExpS && $prana_data): ?>
        <div class="prana-wrap">
          <?php foreach($prana_data as $prana):
            $isNowP = $isNowS && dasa_now($prana['start'],$prana['end'],$today);
            $pc=$COLORS[$prana['planet']]??'#666';
            $pi_=$ICONS[$prana['planet']]??'';
          ?>
          <div class="prana-row <?php echo $isNowP?'now':'';?>">
            <span class="pdot" style="background:<?php echo $pc;?>;width:5px;height:5px;"></span>
            <span style="font-size:10px;font-weight:600;color:var(--navy);min-width:85px;">
              <?php echo esc_html($pi_.' '.$prana['tamil']);?>
              <?php if($isNowP):?><span class="dasa-now-pill" style="font-size:8px;">NOW</span><?php endif;?>
            </span>
            <span style="font-size:9px;color:var(--muted);"><?php echo esc_html($prana['start']);?> → <?php echo esc_html($prana['end']);?></span>
          </div>
          <?php endforeach;?>
        </div>
        <?php endif;?>
        <?php endforeach;?>
      </div>
      <?php endif;?>
      <?php endforeach;?>
    </div>
    <?php endif;?>
    <?php endforeach;?>
  </div>
  <?php endif;?>
</div>
<?php endforeach;?>
</div>

<!-- SUMMARY TABLE -->
<div class="dasa-sec-head"><span class="dasa-sec-dot"></span><span class="dasa-sec-title">மஹா தசா சுருக்கம்</span></div>
<table class="dasa-sum-tbl">
  <tr><th>கிரகம்</th><th>ஆரம்பம்</th><th>முடிவு</th><th>ஆண்டுகள்</th><th>நிலை</th></tr>
  <?php foreach($data['dasas'] as $dasa):
    $isNow=dasa_now($dasa['start'],$dasa['end'],$today);
    $c=$COLORS[$dasa['planet']]??'#666';
    $ic=$ICONS[$dasa['planet']]??'';
  ?>
  <tr class="<?php echo $isNow?'now-row':'';?>">
    <td><span class="pdot" style="background:<?php echo $c;?>;width:10px;height:10px;display:inline-block;margin-right:6px;vertical-align:middle;"></span><?php echo esc_html($ic.' '.$dasa['tamil']);?><?php if($isNow):?><span class="dasa-now-pill" style="font-size:10px;margin-left:4px;">NOW</span><?php endif;?></td>
    <td><?php echo esc_html($dasa['start']);?></td>
    <td><?php echo esc_html($dasa['end']);?></td>
    <td><?php echo esc_html($dasa['years']);?></td>
    <td><?php if($isNow):?><span style="color:#E65100;font-weight:600;"><?php echo esc_html(dasa_left($dasa['end'],$today));?> மீதமுள்ளது</span><?php elseif($dasa['end']<$today):?><span style="color:#999;">முடிந்தது</span><?php else:?><span style="color:var(--teal);">வரும்</span><?php endif;?></td>
  </tr>
  <?php endforeach;?>
</table>

<script>
// Time picker
function updH(){
  var h=parseInt(document.getElementById('dhr').value)||0;
  var m=parseInt(document.getElementById('dmn').value)||0;
  var ap=document.getElementById('dap').value;
  if(ap==='PM'&&h!==12)h+=12;
  if(ap==='AM'&&h===12)h=0;
  document.getElementById('dhh').value=(h+m/60).toFixed(4);
}
document.getElementById('dhr').addEventListener('input',updH);
document.getElementById('dmn').addEventListener('input',updH);
document.getElementById('dap').addEventListener('change',updH);
if(document.getElementById('dhr').value)updH();

// City autocomplete
var dc=document.getElementById('dcity'),ds=document.getElementById('dsug'),dt=null;
dc.addEventListener('input',function(){
  clearTimeout(dt);var q=this.value.trim();
  if(q.length<3){ds.style.display='none';return;}
  dt=setTimeout(function(){
    fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(q)+'&format=json&limit=5&countrycodes=in',{headers:{'Accept-Language':'ta,en'}})
    .then(function(r){return r.json();}).then(function(data){
      ds.innerHTML='';if(!data.length){ds.style.display='none';return;}
      data.forEach(function(p){
        var d=document.createElement('div');
        d.textContent=p.display_name.split(',').slice(0,3).join(', ');
        d.addEventListener('click',function(){
          dc.value=d.textContent;
          document.getElementById('dcity-h').value=d.textContent;
          document.getElementById('dlat').value=parseFloat(p.lat).toFixed(4);
          document.getElementById('dlng').value=parseFloat(p.lon).toFixed(4);
          ds.style.display='none';
        });
        ds.appendChild(d);
      });
      ds.style.display='block';
    });
  },400);
});
document.addEventListener('click',function(e){if(!dc.contains(e.target)&&!ds.contains(e.target))ds.style.display='none';});
document.getElementById('dasa-form').addEventListener('submit',function(){if(document.getElementById('dhr').value)updH();});
</script>

<?php endif;?>
<div class="dasa-footer"><b>Krishnalaya Astro Centre</b> · விம்சோத்தரி தசா · Swiss Ephemeris · Vellore</div>
</div>
    <?php return ob_get_clean(); }
