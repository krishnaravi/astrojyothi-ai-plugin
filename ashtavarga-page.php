<?php
/**
 * AstroJyothi Ashtavarga - Premium Edition
 * Shortcode: [astrojyothi_ashtavarga]
 */
if (!defined('ABSPATH')) { exit; }

add_shortcode('astrojyothi_ashtavarga', 'ajai_render_ashtavarga');

function ajai_render_ashtavarga($atts) {
    $atts = shortcode_atts(array('lat'=>'12.9165','lng'=>'79.1325'), $atts);
    $year  = isset($_GET['by'])  ? intval($_GET['by'])   : '';
    $month = isset($_GET['bm'])  ? intval($_GET['bm'])   : '';
    $day   = isset($_GET['bd'])  ? intval($_GET['bd'])   : '';
    $bh_raw = isset($_GET['bh']) ? trim($_GET['bh']) : '';
    $hours = '';
    if($bh_raw !== ''){
        // Support both 7.4667 (decimal) and 7.28 (HH.MM format)
        if(strpos($bh_raw, '.') !== false){
            $parts = explode('.', $bh_raw);
            $h = intval($parts[0]);
            $m_str = $parts[1] ?? '0';
            // If minutes part > 59, it's already decimal hours
            $m_val = intval(str_pad($m_str, 2, '0'));
            if($m_val > 59){
                $hours = floatval($bh_raw); // decimal format
            } else {
                $hours = $h + ($m_val / 60); // HH.MM format
            }
        } else {
            $hours = floatval($bh_raw);
        }
    }
    $lat   = isset($_GET['lat']) ? floatval($_GET['lat']): floatval($atts['lat']);
    $lng   = isset($_GET['lng']) ? floatval($_GET['lng']): floatval($atts['lng']);
    $data  = null; $error = '';

    if ($year && $month && $day && $hours !== '') {
        $response = wp_remote_get(
            "http://127.0.0.1:3100/api/ashtavarga?year={$year}&month={$month}&day={$day}&hours={$hours}&lat={$lat}&lng={$lng}",
            array('timeout'=>20)
        );
        if (is_wp_error($response)) { $error = 'API error'; }
        else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['success'])) { $error = $data['error']??'error'; $data=null; }
        }
    }

    $PLANET_ORDER = array('Sun','Moon','Mars','Mercury','Jupiter','Venus','Saturn','Sarva');
    $PLANET_TAMIL = array(
        'Sun'=>'சூரியன்','Moon'=>'சந்திரன்','Mars'=>'செவ்வாய்',
        'Mercury'=>'புதன்','Jupiter'=>'வியாழன்','Venus'=>'வெள்ளி',
        'Saturn'=>'சனி','Sarva'=>'சர்வம்'
    );
    $PLANET_COLORS = array(
        'Sun'=>'#FF6B35','Moon'=>'#4A90D9','Mars'=>'#E63946',
        'Mercury'=>'#2DC653','Jupiter'=>'#D4A800','Venus'=>'#F4A261',
        'Saturn'=>'#6D6875','Sarva'=>'#1A3A5C'
    );
    $PLANET_ICONS = array(
        'Sun'=>'☀️','Moon'=>'🌙','Mars'=>'🔴','Mercury'=>'💚',
        'Jupiter'=>'🟡','Venus'=>'⭐','Saturn'=>'🪐','Sarva'=>'🔱'
    );

    // Good/Bad threshold for coloring cells
    function av_cell_class($val, $is_sarva) {
        if ($is_sarva) {
            if ($val >= 30) return 'av-high';
            if ($val >= 25) return 'av-mid';
            return 'av-low';
        }
        if ($val >= 5) return 'av-high';
        if ($val >= 4) return 'av-mid';
        if ($val >= 3) return 'av-ok';
        return 'av-low';
    }

    ob_start();
    ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Noto+Sans+Tamil:wght@300;400;500;600&display=swap');
#av-root{
  --navy:#1A3A5C;--teal:#006064;--green:#1B5E20;
  --bg:#EAF4FB;--card:#fff;--border:#B0D4E8;
  --text:#1A1A2E;--muted:#546E7A;
  font-family:'Noto Sans Tamil',Arial,sans-serif;
  background:var(--bg);color:var(--text);
  max-width:1050px;margin:0 auto;
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,0.12);
  border:1px solid var(--border);
}
.av-hero{background:linear-gradient(135deg,#1A3A5C 0%,#006064 60%,#1B5E20 100%);color:#fff;padding:24px 24px 18px;border-bottom:3px solid #A5D6A7;position:relative;overflow:hidden;}
.av-hero::before{content:'ॐ';font-size:160px;color:rgba(255,255,255,0.04);position:absolute;right:-10px;top:-30px;font-family:serif;line-height:1;}
.av-eyebrow{font-family:'Cinzel',serif;font-size:10px;letter-spacing:4px;color:#A5D6A7;text-transform:uppercase;margin-bottom:4px;}
.av-title{font-family:'Cinzel',serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:3px;}
.av-sub{font-size:12px;color:rgba(255,255,255,0.75);}
/* FORM */
.av-form{background:#E3F2FD;border-bottom:2px solid var(--border);padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
.av-fg{display:flex;flex-direction:column;gap:4px;position:relative;}
.av-fg label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;font-weight:600;}
.av-fg input,.av-fg select{background:#fff;border:1px solid var(--border);color:var(--text);padding:7px 10px;border-radius:8px;font-size:13px;font-family:inherit;}
.av-fg input[name=by]{width:70px;}
.av-fg input[name=bm],.av-fg input[name=bd]{width:55px;}
.av-time-row{display:flex;gap:4px;align-items:center;}
.av-time-row input{width:44px!important;text-align:center;font-weight:600;}
.av-time-row select{width:65px!important;}
.av-time-sep{font-size:18px;font-weight:700;color:var(--navy);}
.av-city{width:150px!important;}
.av-latlong{display:flex;gap:6px;}
.av-latlong input{width:78px!important;}
.av-city-sug{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 8px 8px;z-index:100;max-height:160px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
.av-city-sug div{padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;}
.av-city-sug div:hover{background:#E3F2FD;}
.av-btn{background:var(--navy);color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;align-self:flex-end;}
.av-btn:hover{background:var(--teal);}
/* INFO BAR */
.av-info{display:flex;border-bottom:2px solid var(--border);background:#F0FFF4;flex-wrap:wrap;}
.av-info-item{flex:1;min-width:120px;padding:10px 14px;border-right:1px solid var(--border);text-align:center;}
.av-info-item:last-child{border-right:none;}
.av-info-label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;margin-bottom:3px;font-weight:600;}
.av-info-val{font-size:14px;font-weight:700;color:var(--navy);}
/* SECTION HEAD */
.av-sec-head{padding:10px 18px;background:linear-gradient(90deg,#E3F2FD,#E8F5E9);border-bottom:1px solid var(--border);border-top:2px solid var(--border);display:flex;align-items:center;gap:8px;}
.av-sec-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);}
.av-sec-title{font-size:11px;letter-spacing:3px;color:var(--teal);text-transform:uppercase;font-weight:700;}
/* MAIN GRID TABLE */
.av-table-wrap{overflow-x:auto;background:#fff;}
.av-tbl{border-collapse:collapse;min-width:700px;width:100%;}
.av-tbl th{padding:8px 6px;font-size:10px;letter-spacing:1px;color:var(--navy);text-transform:uppercase;font-weight:700;border-bottom:2px solid var(--border);text-align:center;background:#E3F2FD;white-space:nowrap;}
.av-tbl th.planet-th{text-align:left;padding-left:12px;min-width:100px;}
.av-tbl td{padding:8px 6px;border-bottom:1px solid #E0EEF8;text-align:center;font-size:13px;font-weight:600;}
.av-tbl tr:hover td{background:#F0F8FF;}
.av-tbl td.planet-td{text-align:left;padding-left:12px;font-size:13px;}
.av-tbl td.total-td{font-size:14px;font-weight:700;border-left:2px solid var(--border);}
/* Cell colors */
.av-high{background:#E8F5E9;color:#1B5E20;}
.av-mid{background:#FFF9C4;color:#F57F17;}
.av-ok{background:#FFF3E0;color:#E65100;}
.av-low{background:#FFEBEE;color:#C62828;}
/* Sarva row special */
.av-sarva td{background:#E3F2FD!important;font-weight:700;border-top:2px solid var(--border);}
.av-sarva td.av-high{background:#C8E6C9!important;color:#1B5E20;}
.av-sarva td.av-mid{background:#FFF9C4!important;color:#F57F17;}
.av-sarva td.av-low{background:#FFCDD2!important;color:#C62828;}
/* PLANET DETAIL CARDS */
.av-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;}
@media(max-width:700px){.av-cards{grid-template-columns:repeat(2,1fr);}}
.av-card{border-right:1px solid #E0EEF8;border-bottom:1px solid #E0EEF8;padding:14px 12px;}
.av-card:nth-child(even){background:#F8FCFF;}
.av-card-header{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.av-card-icon{font-size:18px;}
.av-card-planet{font-size:14px;font-weight:700;color:var(--navy);}
.av-card-total{font-size:12px;color:var(--muted);}
.av-mini-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:2px;}
.av-mini-cell{text-align:center;padding:4px 2px;border-radius:4px;font-size:11px;font-weight:600;}
.av-mini-label{font-size:9px;color:var(--muted);text-align:center;margin-bottom:1px;}
/* LEGEND */
.av-legend{display:flex;gap:16px;padding:12px 20px;background:#F8FCFF;border-top:1px solid var(--border);flex-wrap:wrap;}
.av-leg-item{display:flex;align-items:center;gap:6px;font-size:12px;}
.av-leg-dot{width:16px;height:16px;border-radius:4px;}
/* EMPTY/ERROR */
.av-empty{padding:40px;text-align:center;color:var(--muted);background:#fff;}
.av-empty-icon{font-size:48px;margin-bottom:12px;}
.av-error{padding:20px;background:#FFEBEE;color:#C62828;border-left:4px solid #C62828;margin:16px;}
/* FOOTER */
.av-footer{padding:12px 20px;text-align:center;font-size:11px;color:var(--muted);background:linear-gradient(90deg,#E3F2FD,#E8F5E9);border-top:2px solid var(--border);}
.av-footer b{color:var(--navy);}
</style>

<div id="av-root">
<!-- HERO -->
<div class="av-hero">
  <div class="av-eyebrow">Krishnalaya Astro Centre</div>
  <div class="av-title">அஷ்டவர்கம் · Ashtavarga</div>
  <div class="av-sub">பிண்டாஷ்டவர்கம் · சர்வாஷ்டவர்கம் | Swiss Ephemeris</div>
</div>

<!-- FORM -->
<form class="av-form" method="get" id="av-form">
  <div class="av-fg">
    <label>ஆண்டு</label>
    <input type="number" name="by" placeholder="1985" value="<?php echo esc_attr($year);?>" min="1900" max="2100">
  </div>
  <div class="av-fg">
    <label>மாதம்</label>
    <input type="number" name="bm" placeholder="6" value="<?php echo esc_attr($month);?>" min="1" max="12">
  </div>
  <div class="av-fg">
    <label>நாள்</label>
    <input type="number" name="bd" placeholder="15" value="<?php echo esc_attr($day);?>" min="1" max="31">
  </div>
  <div class="av-fg">
    <label>நேரம் IST (மணி.நிமிடம்)</label>
    <input type="text" name="bh" placeholder="7.28" style="width:85px;" inputmode="decimal"
      value="<?php if($hours){$h=floor($hours);$m=round(($hours-$h)*60);echo $h.'.'.str_pad($m,2,'0',STR_PAD_LEFT);}?>">
    <small style="font-size:10px;color:#666;">உ.கா: 7.28 = 7:28</small>
  </div>
  <div class="av-fg">
    <label>ஊர் பெயர்</label>
    <input type="text" id="av-city" class="av-city" placeholder="நகரம்..." value="<?php echo esc_attr(isset($_GET['city'])?$_GET['city']:'Vellore');?>">
    <input type="hidden" name="city" id="av-city-h" value="<?php echo esc_attr(isset($_GET['city'])?$_GET['city']:'Vellore');?>">
    <div class="av-city-sug" id="av-sug"></div>
  </div>
  <div class="av-fg">
    <label>Lat / Lng</label>
    <div class="av-latlong">
      <input type="number" name="lat" id="av-lat" placeholder="12.9165" value="<?php echo esc_attr($lat);?>" step="0.0001">
      <input type="number" name="lng" id="av-lng" placeholder="79.1325" value="<?php echo esc_attr($lng);?>" step="0.0001">
    </div>
  </div>
  <button type="submit" class="av-btn">கணக்கிடு →</button>
</form>

<?php if ($error): ?>
<div class="av-error">பிழை: <?php echo esc_html($error);?></div>
<?php elseif (!$data): ?>
<div class="av-empty">
  <div class="av-empty-icon">🔱</div>
  <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:6px;">பிறந்த விவரங்களை உள்ளிடுங்கள்</div>
  <div style="font-size:13px;">7 கிரகங்கள் + சர்வம் | 12 ராசிகள்</div>
</div>
<?php else:
  $av = $data['ashtavarga'];
  $rasi_names = $data['rasi_names'];
  $lagna = $data['lagna'];
  $positions = $data['planet_positions'] ?? array();
?>

<!-- INFO BAR -->
<div class="av-info">
  <div class="av-info-item">
    <div class="av-info-label">பிறந்த தேதி</div>
    <div class="av-info-val"><?php echo esc_html("{$data['birth']['day']}/{$data['birth']['month']}/{$data['birth']['year']}");?></div>
  </div>
  <div class="av-info-item">
    <div class="av-info-label">லக்னம்</div>
    <div class="av-info-val"><?php echo esc_html($lagna['rasiName']??'');?></div>
  </div>
  <div class="av-info-item">
    <div class="av-info-label">சர்வம் மொத்தம்</div>
    <div class="av-info-val"><?php echo esc_html(array_sum($av['Sarva']['points']??array()));?></div>
  </div>
  <div class="av-info-item">
    <div class="av-info-label">சிறந்த ராசி</div>
    <?php
    $sarva_pts = $av['Sarva']['points'] ?? array_fill(0,12,0);
    $max_idx = array_search(max($sarva_pts), $sarva_pts);
    ?>
    <div class="av-info-val" style="color:var(--green);"><?php echo esc_html($rasi_names[$max_idx]??'');?> (<?php echo esc_html(max($sarva_pts));?>)</div>
  </div>
  <div class="av-info-item">
    <div class="av-info-label">பலவீன ராசி</div>
    <?php $min_idx = array_search(min($sarva_pts), $sarva_pts); ?>
    <div class="av-info-val" style="color:#C62828;"><?php echo esc_html($rasi_names[$min_idx]??'');?> (<?php echo esc_html(min($sarva_pts));?>)</div>
  </div>
</div>

<!-- MAIN GRID -->
<div class="av-sec-head"><span class="av-sec-dot"></span><span class="av-sec-title">பிண்டாஷ்டவர்கம் அட்டவணை</span></div>
<div class="av-table-wrap">
<table class="av-tbl">
  <tr>
    <th class="planet-th">கிரகம்</th>
    <?php foreach($rasi_names as $rn): ?>
    <th><?php echo esc_html(mb_substr($rn,0,3));?></th>
    <?php endforeach; ?>
    <th>மொத்தம்</th>
  </tr>
  <?php foreach($PLANET_ORDER as $pkey):
    if (!isset($av[$pkey])) continue;
    $pdata = $av[$pkey];
    $points = $pdata['points'];
    $total  = $pdata['total'];
    $color  = $PLANET_COLORS[$pkey] ?? '#666';
    $icon   = $PLANET_ICONS[$pkey] ?? '';
    $tamil  = $PLANET_TAMIL[$pkey] ?? $pkey;
    $is_sarva = ($pkey === 'Sarva');
    $pos_info = '';
    if (!$is_sarva && isset($positions[strtoupper($pkey)])) {
      $pos = $positions[strtoupper($pkey)];
      $pos_info = $pos['rasi'].' '.$pos['degree'].'°';
    }
  ?>
  <tr class="<?php echo $is_sarva?'av-sarva':'';?>">
    <td class="planet-td">
      <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $color;?>;margin-right:6px;vertical-align:middle;"></span>
      <?php echo esc_html($icon.' '.$tamil);?>
      <?php if($pos_info):?><div style="font-size:10px;color:var(--muted);font-weight:400;"><?php echo esc_html($pos_info);?></div><?php endif;?>
    </td>
    <?php foreach($points as $i=>$val):
      $cls = av_cell_class($val, $is_sarva);
    ?>
    <td class="<?php echo $cls;?>"><?php echo esc_html($val);?></td>
    <?php endforeach;?>
    <td class="total-td" style="color:<?php echo $color;?>;"><?php echo esc_html($total);?></td>
  </tr>
  <?php endforeach;?>
</table>
</div>

<!-- LEGEND -->
<div class="av-legend">
  <div class="av-leg-item"><div class="av-leg-dot av-high"></div> சிறந்தது (≥5 / சர்வம்≥30)</div>
  <div class="av-leg-item"><div class="av-leg-dot av-mid"></div> நல்லது (4 / சர்வம்≥25)</div>
  <div class="av-leg-item"><div class="av-leg-dot av-ok"></div> சாதாரணம் (3)</div>
  <div class="av-leg-item"><div class="av-leg-dot av-low"></div> பலவீனம் (≤2)</div>
</div>

<!-- PLANET CARDS -->
<div class="av-sec-head"><span class="av-sec-dot"></span><span class="av-sec-title">கிரக விவர அட்டவணை</span></div>
<div class="av-cards">
  <?php foreach($PLANET_ORDER as $pkey):
    if (!isset($av[$pkey]) || $pkey==='Sarva') continue;
    $pdata=$av[$pkey]; $points=$pdata['points'];
    $color=$PLANET_COLORS[$pkey]??'#666';
    $icon=$PLANET_ICONS[$pkey]??'';
    $tamil=$PLANET_TAMIL[$pkey]??$pkey;
    $max_v=max($points); $min_v=min($points);
    $max_r=$rasi_names[array_search($max_v,$points)];
    $min_r=$rasi_names[array_search($min_v,$points)];
  ?>
  <div class="av-card">
    <div class="av-card-header">
      <span class="av-card-icon"><?php echo esc_html($icon);?></span>
      <div>
        <div class="av-card-planet" style="color:<?php echo $color;?>;"><?php echo esc_html($tamil);?></div>
        <div class="av-card-total">மொத்தம்: <?php echo esc_html($pdata['total']);?> | சராசரி: <?php echo esc_html(number_format($pdata['total']/12,1));?></div>
      </div>
    </div>
    <div class="av-mini-grid">
      <?php foreach($points as $i=>$val):
        $cls=av_cell_class($val,false);
      ?>
      <div>
        <div class="av-mini-label"><?php echo esc_html(mb_substr($rasi_names[$i],0,2));?></div>
        <div class="av-mini-cell <?php echo $cls;?>"><?php echo esc_html($val);?></div>
      </div>
      <?php endforeach;?>
    </div>
    <div style="margin-top:8px;font-size:11px;display:flex;justify-content:space-between;">
      <span style="color:var(--green);">↑ <?php echo esc_html($max_r);?> (<?php echo esc_html($max_v);?>)</span>
      <span style="color:#C62828;">↓ <?php echo esc_html($min_r);?> (<?php echo esc_html($min_v);?>)</span>
    </div>
  </div>
  <?php endforeach;?>
</div>

<!-- SARVA DETAIL -->
<div class="av-sec-head"><span class="av-sec-dot"></span><span class="av-sec-title">சர்வாஷ்டவர்கம்</span></div>
<div style="background:#fff;padding:16px 20px;">
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-bottom:12px;">
    <?php foreach($sarva_pts as $i=>$val):
      $cls=av_cell_class($val,true);
    ?>
    <div style="text-align:center;padding:10px 6px;border-radius:8px;" class="<?php echo $cls;?>">
      <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo esc_html($rasi_names[$i]);?></div>
      <div style="font-size:20px;font-weight:700;"><?php echo esc_html($val);?></div>
    </div>
    <?php endforeach;?>
  </div>
  <div style="font-size:12px;color:var(--muted);text-align:center;">
    மொத்தம்: <strong><?php echo esc_html(array_sum($sarva_pts));?></strong> |
    சராசரி: <strong><?php echo esc_html(number_format(array_sum($sarva_pts)/12,1));?></strong> |
    சிறந்தது: <strong style="color:var(--green);"><?php echo esc_html($rasi_names[$max_idx]);?> (<?php echo esc_html(max($sarva_pts));?>)</strong> |
    பலவீனம்: <strong style="color:#C62828;"><?php echo esc_html($rasi_names[$min_idx]);?> (<?php echo esc_html(min($sarva_pts));?>)</strong>
  </div>
</div>

<script>
function avUpdH(){
  var h=parseInt(document.getElementById('av-hr').value)||0;
  var m=parseInt(document.getElementById('av-mn').value)||0;
  var ap=document.getElementById('av-ap').value;
  if(ap==='PM'&&h!==12)h+=12;
  if(ap==='AM'&&h===12)h=0;
  document.getElementById('av-hh').value=(h+m/60).toFixed(4);
}
document.getElementById('av-hr').addEventListener('input',avUpdH);
document.getElementById('av-mn').addEventListener('input',avUpdH);
document.getElementById('av-ap').addEventListener('change',avUpdH);
// Page load: hidden field already has correct PHP value, don't override
var avc=document.getElementById('av-city'),avs=document.getElementById('av-sug'),avt=null;
avc.addEventListener('input',function(){
  clearTimeout(avt);var q=this.value.trim();
  if(q.length<3){avs.style.display='none';return;}
  avt=setTimeout(function(){
    fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(q)+'&format=json&limit=5&countrycodes=in',{headers:{'Accept-Language':'ta,en'}})
    .then(function(r){return r.json();}).then(function(data){
      avs.innerHTML='';if(!data.length){avs.style.display='none';return;}
      data.forEach(function(p){
        var d=document.createElement('div');
        d.textContent=p.display_name.split(',').slice(0,3).join(', ');
        d.addEventListener('click',function(){
          avc.value=d.textContent;
          document.getElementById('av-city-h').value=d.textContent;
          document.getElementById('av-lat').value=parseFloat(p.lat).toFixed(4);
          document.getElementById('av-lng').value=parseFloat(p.lon).toFixed(4);
          avs.style.display='none';
        });
        avs.appendChild(d);
      });
      avs.style.display='block';
    });
  },400);
});
document.addEventListener('click',function(e){if(!avc.contains(e.target)&&!avs.contains(e.target))avs.style.display='none';});
document.getElementById('av-form').addEventListener('submit',function(e){
  var hr = document.getElementById('av-hr').value;
  var mn = document.getElementById('av-mn').value;
  var ap = document.getElementById('av-ap').value;
  if(hr && mn){
    var h=parseInt(hr)||0;
    var m=parseInt(mn)||0;
    if(ap==='PM'&&h!==12)h+=12;
    if(ap==='AM'&&h===12)h=0;
    document.getElementById('av-hh').value=(h+m/60).toFixed(4);
  }
});
</script>

<?php endif;?>
<div class="av-footer"><b>Krishnalaya Astro Centre</b> · அஷ்டவர்கம் · Swiss Ephemeris · Vellore</div>
</div>
    <?php return ob_get_clean(); }
