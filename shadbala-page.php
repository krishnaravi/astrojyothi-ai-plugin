<?php
/**
 * AstroJyothi Shadbala Page
 * Shortcode: [astrojyothi_shadbala]
 */
if (!defined('ABSPATH')) { exit; }

add_shortcode('astrojyothi_shadbala', 'ajai_render_shadbala');

function ajai_render_shadbala($atts) {
    $atts = shortcode_atts(array('lat' => '12.9165', 'lng' => '79.1325'), $atts);

    // Get birth details from GET params
    $year  = isset($_GET['by']) ? intval($_GET['by'])   : '';
    $month = isset($_GET['bm']) ? intval($_GET['bm'])   : '';
    $day   = isset($_GET['bd']) ? intval($_GET['bd'])   : '';
    $hours = isset($_GET['bh']) ? floatval($_GET['bh']) : '';
    $lat   = isset($_GET['lat'])? floatval($_GET['lat']): floatval($atts['lat']);
    $lng   = isset($_GET['lng'])? floatval($_GET['lng']): floatval($atts['lng']);

    $data  = null;
    $error = '';

    if ($year && $month && $day && $hours !== '') {
        $response = wp_remote_get(
            "http://127.0.0.1:3100/api/shadbala?year={$year}&month={$month}&day={$day}&hours={$hours}&lat={$lat}&lng={$lng}",
            array('timeout' => 20)
        );
        if (is_wp_error($response)) {
            $error = 'API connection failed.';
        } else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['success'])) {
                $error = $data['error'] ?? 'Unknown error';
                $data = null;
            }
        }
    }

    $BALA_LABELS = array(
        'sthana_bala'     => array('label' => 'ஸ்தான பலம்',    'en' => 'Sthana',     'desc' => 'Positional'),
        'dig_bala'        => array('label' => 'திக் பலம்',      'en' => 'Dig',        'desc' => 'Directional'),
        'kala_bala'       => array('label' => 'கால பலம்',       'en' => 'Kala',       'desc' => 'Temporal'),
        'cheshta_bala'    => array('label' => 'சேஷ்ட பலம்',    'en' => 'Cheshta',    'desc' => 'Motional'),
        'naisargika_bala' => array('label' => 'நைசர்கிக பலம்', 'en' => 'Naisargika', 'desc' => 'Natural'),
        'drik_bala'       => array('label' => 'த்ருக் பலம்',   'en' => 'Drik',       'desc' => 'Aspectual'),
    );

    ob_start();
    ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Noto+Sans+Tamil:wght@300;400;500;600&display=swap');
#shad-root{
  --navy:#1A3A5C;--teal:#006064;--green:#1B5E20;
  --bg:#EAF4FB;--card:#fff;--border:#B0D4E8;
  --text:#1A1A2E;--muted:#546E7A;
  --bad:#C62828;--good:#1B5E20;--mid:#E65100;
  --bad-bg:#FFEBEE;--good-bg:#E8F5E9;--mid-bg:#FFF3E0;
  --now-bg:#FFF9C4;
  font-family:'Noto Sans Tamil',Arial,sans-serif;
  background:var(--bg);color:var(--text);
  max-width:960px;margin:0 auto;
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,0.12);
  border:1px solid var(--border);
}
.shad-hero{
  background:linear-gradient(135deg,#1A3A5C 0%,#006064 60%,#1B5E20 100%);
  color:#fff;padding:24px 24px 18px;border-bottom:3px solid #A5D6A7;
  position:relative;overflow:hidden;
}
.shad-hero::before{content:'ॐ';font-size:160px;color:rgba(255,255,255,0.04);
  position:absolute;right:-10px;top:-30px;font-family:serif;line-height:1;}
.shad-eyebrow{font-family:'Cinzel',serif;font-size:10px;letter-spacing:4px;color:#A5D6A7;text-transform:uppercase;margin-bottom:4px;}
.shad-title{font-family:'Cinzel',serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:3px;}
.shad-sub{font-size:12px;color:rgba(255,255,255,0.75);}

/* FORM */
.shad-form{
  background:#E3F2FD;border-bottom:2px solid var(--border);
  padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;
}
.shad-form-group{display:flex;flex-direction:column;gap:4px;position:relative;}
.shad-form-group label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;font-weight:600;}
.shad-form-group input{
  background:#fff;border:1px solid var(--border);color:var(--text);
  padding:7px 10px;border-radius:8px;font-size:13px;font-family:inherit;width:90px;
}
.shad-form-group input[name=by]{width:70px;}
.shad-form-group select{
  background:#fff;border:1px solid var(--border);color:var(--text);
  padding:7px 10px;border-radius:8px;font-size:13px;font-family:inherit;width:65px;
}
.shad-city-input{width:180px !important;}
.shad-time-row{display:flex;gap:4px;align-items:center;}
.shad-time-row input{width:46px !important;text-align:center;letter-spacing:2px;font-weight:600;}
.shad-time-row select{width:68px !important;}
.shad-time-sep{font-size:18px;font-weight:700;color:var(--navy);line-height:1;padding-bottom:2px;}
.shad-city-suggestions{
  position:absolute;top:100%;left:0;right:0;background:#fff;
  border:1px solid var(--border);border-top:none;border-radius:0 0 8px 8px;
  z-index:100;max-height:180px;overflow-y:auto;display:none;
  box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.shad-city-suggestions div{
  padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;
}
.shad-city-suggestions div:hover{background:#E3F2FD;color:var(--navy);}
.shad-latlong{display:flex;gap:8px;}
.shad-latlong input{width:80px !important;}
.shad-btn{
  background:var(--navy);color:#fff;border:none;
  padding:8px 20px;border-radius:8px;cursor:pointer;
  font-size:13px;font-weight:600;font-family:inherit;
  align-self:flex-end;
}
.shad-btn:hover{background:var(--teal);}

/* LAGNA BAR */
.shad-lagna{
  display:flex;gap:0;border-bottom:2px solid var(--border);
  background:#F0FFF4;
}
.shad-lagna-item{flex:1;padding:10px 14px;border-right:1px solid var(--border);text-align:center;}
.shad-lagna-item:last-child{border-right:none;}
.shad-l-label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;margin-bottom:3px;font-weight:600;}
.shad-l-val{font-size:14px;font-weight:700;color:var(--navy);}

/* SECTION HEAD */
.shad-section-head{
  padding:10px 18px;background:linear-gradient(90deg,#E3F2FD,#E8F5E9);
  border-bottom:1px solid var(--border);border-top:2px solid var(--border);
  display:flex;align-items:center;gap:8px;
}
.shad-section-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);}
.shad-section-title{font-size:11px;letter-spacing:3px;color:var(--teal);text-transform:uppercase;font-weight:700;}

/* MAIN TABLE */
.shad-tbl{width:100%;border-collapse:collapse;background:#fff;}
.shad-tbl th{
  padding:10px 12px;font-size:10px;letter-spacing:1px;color:var(--navy);
  text-transform:uppercase;font-weight:700;border-bottom:2px solid var(--border);
  text-align:center;background:#E3F2FD;
}
.shad-tbl th.left{text-align:left;}
.shad-tbl td{
  padding:10px 12px;border-bottom:1px solid #E0EEF8;
  font-size:13px;color:var(--text);text-align:center;font-weight:500;
}
.shad-tbl tr:nth-child(even) td{background:#F8FCFF;}
.shad-tbl tr:hover td{background:#EBF5FB;}
.shad-tbl td.left{text-align:left;}
.shad-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid transparent;}
.shad-bad{background:var(--bad-bg);color:var(--bad);border-color:#FFCDD2;}
.shad-good{background:var(--good-bg);color:var(--good);border-color:#C8E6C9;}
.shad-mid{background:var(--mid-bg);color:var(--mid);border-color:#FFE0B2;}
.shad-vstrong{background:#E8EAF6;color:#283593;border-color:#C5CAE9;}

/* PROGRESS BAR */
.shad-bar-wrap{background:#E0EEF8;border-radius:20px;height:6px;width:80px;display:inline-block;vertical-align:middle;margin-left:6px;}
.shad-bar-fill{height:6px;border-radius:20px;transition:width 0.3s;}

/* PLANET CARD GRID */
.shad-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;}
@media(max-width:700px){.shad-cards{grid-template-columns:repeat(2,1fr);}}
.shad-card{
  border-right:1px solid #E0EEF8;border-bottom:1px solid #E0EEF8;
  padding:14px 12px;background:#fff;
}
.shad-card:nth-child(even){background:#F8FCFF;}
.shad-card-planet{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:2px;}
.shad-card-rasi{font-size:11px;color:var(--muted);margin-bottom:8px;}
.shad-card-bar{margin-bottom:4px;}
.shad-card-bar-label{font-size:10px;color:var(--muted);display:flex;justify-content:space-between;margin-bottom:2px;}
.shad-card-bar-track{background:#E0EEF8;border-radius:4px;height:5px;}
.shad-card-bar-fill{height:5px;border-radius:4px;}
.shad-card-total{margin-top:8px;padding-top:6px;border-top:1px solid #E0EEF8;
  display:flex;justify-content:space-between;align-items:center;}
.shad-card-rupas{font-size:16px;font-weight:700;color:var(--navy);}
.shad-card-pct{font-size:11px;color:var(--muted);}

/* SUMMARY */
.shad-summary{
  display:flex;border-bottom:2px solid var(--border);
}
.shad-sum-item{flex:1;padding:12px 14px;border-right:1px solid var(--border);text-align:center;background:#F0FFF4;}
.shad-sum-item:nth-child(even){background:#F0F8FF;}
.shad-sum-item:last-child{border-right:none;}
.shad-sum-label{font-size:10px;letter-spacing:2px;color:var(--teal);text-transform:uppercase;margin-bottom:3px;font-weight:600;}
.shad-sum-val{font-size:14px;font-weight:700;color:var(--navy);}

/* FOOTER */
.shad-footer{
  padding:12px 20px;text-align:center;font-size:11px;color:var(--muted);
  background:linear-gradient(90deg,#E3F2FD,#E8F5E9);
  border-top:2px solid var(--border);letter-spacing:1px;
}
.shad-footer b{color:var(--navy);}

.shad-empty{padding:40px;text-align:center;color:var(--muted);background:#fff;}
.shad-empty-icon{font-size:48px;margin-bottom:12px;}
.shad-error{padding:20px;background:#FFEBEE;color:var(--bad);border-left:4px solid var(--bad);margin:16px;}
</style>

<div id="shad-root">

<!-- HERO -->
<div class="shad-hero">
  <div class="shad-eyebrow">Krishnalaya Astro Centre</div>
  <div class="shad-title">ஷட்பலம் · Shadbala</div>
  <div class="shad-sub">Six-fold Planetary Strength Analysis | Swiss Ephemeris</div>
</div>

<!-- BIRTH DETAILS FORM -->
<form class="shad-form" method="get" id="shad-birth-form">

  <!-- Date fields -->
  <div class="shad-form-group">
    <label>ஆண்டு</label>
    <input type="number" name="by" placeholder="1985" value="<?php echo esc_attr($year); ?>" min="1900" max="2100">
  </div>
  <div class="shad-form-group">
    <label>மாதம்</label>
    <input type="number" name="bm" placeholder="6" value="<?php echo esc_attr($month); ?>" min="1" max="12">
  </div>
  <div class="shad-form-group">
    <label>நாள்</label>
    <input type="number" name="bd" placeholder="15" value="<?php echo esc_attr($day); ?>" min="1" max="31">
  </div>

  <!-- Time picker HH:MM AM/PM -->
  <div class="shad-form-group">
    <label>நேரம் (IST)</label>
    <div class="shad-time-row">
      <input type="text" id="shad-hr" placeholder="07" maxlength="2" pattern="[0-9]*" inputmode="numeric" value="<?php
        if($hours){ $h=floor($hours); $ampm=$h>=12?'PM':'AM'; $h12=$h%12; if($h12==0)$h12=12; echo $h12; }
      ?>">
      <span class="shad-time-sep">:</span>
      <input type="text" id="shad-min" placeholder="30" maxlength="2" pattern="[0-9]*" inputmode="numeric" value="<?php
        if($hours){ echo str_pad(round(($hours-floor($hours))*60),2,'0',STR_PAD_LEFT); }
      ?>">
      <select id="shad-ampm">
        <option value="AM" <?php if($hours && floor($hours)<12) echo 'selected'; ?>>AM</option>
        <option value="PM" <?php if($hours && floor($hours)>=12) echo 'selected'; ?>>PM</option>
      </select>
      <input type="hidden" name="bh" id="shad-hours-hidden" value="<?php echo esc_attr($hours); ?>">
    </div>
  </div>

  <!-- City autocomplete -->
  <div class="shad-form-group">
    <label>ஊர் பெயர்</label>
    <input type="text" id="shad-city" class="shad-city-input" placeholder="நகரம் தேடுங்கள்..."
      value="<?php echo esc_attr(isset($_GET['city']) ? $_GET['city'] : 'Vellore'); ?>">
    <input type="hidden" name="city" id="shad-city-hidden" value="<?php echo esc_attr(isset($_GET['city']) ? $_GET['city'] : 'Vellore'); ?>">
    <div class="shad-city-suggestions" id="shad-suggestions"></div>
  </div>

  <!-- Lat/Lng (auto-filled) -->
  <div class="shad-form-group">
    <label>Lat / Lng</label>
    <div class="shad-latlong">
      <input type="number" name="lat" id="shad-lat" placeholder="12.9165" value="<?php echo esc_attr($lat); ?>" step="0.0001">
      <input type="number" name="lng" id="shad-lng" placeholder="79.1325" value="<?php echo esc_attr($lng); ?>" step="0.0001">
    </div>
  </div>

  <button type="submit" class="shad-btn" id="shad-submit-btn">கணக்கிடு →</button>
</form>

<script>
(function(){
  // Time conversion: HH:MM AM/PM → decimal hours
  function updateHoursHidden(){
    var hr = parseInt(document.getElementById('shad-hr').value)||0;
    var min = parseInt(document.getElementById('shad-min').value)||0;
    var ampm = document.getElementById('shad-ampm').value;
    if(ampm==='PM' && hr!==12) hr+=12;
    if(ampm==='AM' && hr===12) hr=0;
    var decimal = hr + (min/60);
    document.getElementById('shad-hours-hidden').value = decimal.toFixed(4);
  }
  document.getElementById('shad-hr').addEventListener('input', updateHoursHidden);
  document.getElementById('shad-min').addEventListener('input', updateHoursHidden);
  document.getElementById('shad-ampm').addEventListener('change', updateHoursHidden);

  // City autocomplete using OpenStreetMap Nominatim (free, no API key)
  var cityInput = document.getElementById('shad-city');
  var suggestions = document.getElementById('shad-suggestions');
  var searchTimer = null;

  cityInput.addEventListener('input', function(){
    clearTimeout(searchTimer);
    var q = this.value.trim();
    if(q.length < 3){ suggestions.style.display='none'; return; }
    searchTimer = setTimeout(function(){
      fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(q)+'&format=json&limit=5&countrycodes=in&addressdetails=1', {
        headers: {'Accept-Language': 'ta,en'}
      })
      .then(function(r){ return r.json(); })
      .then(function(data){
        suggestions.innerHTML = '';
        if(!data.length){ suggestions.style.display='none'; return; }
        data.forEach(function(place){
          var div = document.createElement('div');
          var displayName = place.display_name.split(',').slice(0,3).join(', ');
          div.textContent = displayName;
          div.addEventListener('click', function(){
            cityInput.value = displayName;
            document.getElementById('shad-city-hidden').value = displayName;
            document.getElementById('shad-lat').value = parseFloat(place.lat).toFixed(4);
            document.getElementById('shad-lng').value = parseFloat(place.lon).toFixed(4);
            suggestions.style.display = 'none';
          });
          suggestions.appendChild(div);
        });
        suggestions.style.display = 'block';
      })
      .catch(function(){ suggestions.style.display='none'; });
    }, 400);
  });

  // Hide suggestions on outside click
  document.addEventListener('click', function(e){
    if(!cityInput.contains(e.target) && !suggestions.contains(e.target)){
      suggestions.style.display='none';
    }
  });

  // On form submit, validate time
  document.getElementById('shad-birth-form').addEventListener('submit', function(e){
    var hr = document.getElementById('shad-hr').value;
    var min = document.getElementById('shad-min').value;
    if(!hr || !min){
      e.preventDefault();
      alert('நேரம் சரியாக உள்ளிடவும் (மணி மற்றும் நிமிடம்)');
      return;
    }
    updateHoursHidden();
  });

  // Init: update hidden on page load if values present
  if(document.getElementById('shad-hr').value) updateHoursHidden();
})();
</script>

<?php if ($error): ?>
<div class="shad-error">பிழை: <?php echo esc_html($error); ?></div>
<?php elseif (!$data): ?>
<div class="shad-empty">
  <div class="shad-empty-icon">🪐</div>
  <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:6px;">பிறந்த விவரங்களை உள்ளிடுங்கள்</div>
  <div style="font-size:13px;">ஆண்டு, மாதம், நாள், நேரம் (IST decimal — உதாரணம்: 10.5 = 10:30 AM)</div>
</div>
<?php else:
  $lagna = $data['lagna'];
  $shadbala = $data['shadbala'];
  $summary = $data['summary'];
  $birth = $data['birth'];

  $RASI_NAMES = array('','மேஷம்','ரிஷபம்','மிதுனம்','கடகம்','சிம்மம்','கன்னி','துலாம்','விருச்சிகம்','தனுசு','மகரம்','கும்பம்','மீனம்');
  $lagnaRasi = $RASI_NAMES[$lagna['rasi']] ?? $lagna['rasiName'];

  // Bar colors per bala
  $BAR_COLORS = array(
    'sthana_bala'     => '#1565C0',
    'dig_bala'        => '#00838F',
    'kala_bala'       => '#6A1B9A',
    'cheshta_bala'    => '#E65100',
    'naisargika_bala' => '#2E7D32',
    'drik_bala'       => '#AD1457',
  );
?>

<!-- LAGNA BAR -->
<div class="shad-lagna">
  <div class="shad-lagna-item">
    <div class="shad-l-label">பிறந்த தேதி</div>
    <div class="shad-l-val"><?php echo esc_html("{$birth['day']}/{$birth['month']}/{$birth['year']}"); ?></div>
  </div>
  <div class="shad-lagna-item">
    <div class="shad-l-label">நேரம் (IST)</div>
    <div class="shad-l-val"><?php
      $h = floor($birth['hours']); $m = round(($birth['hours'] - $h) * 60);
      echo esc_html(sprintf('%02d:%02d', $h, $m));
    ?></div>
  </div>
  <div class="shad-lagna-item">
    <div class="shad-l-label">லக்னம்</div>
    <div class="shad-l-val"><?php echo esc_html($lagnaRasi); ?></div>
  </div>
  <div class="shad-lagna-item">
    <div class="shad-l-label">லக்ன நீளம்</div>
    <div class="shad-l-val"><?php echo esc_html(number_format($lagna['longitude'], 2)); ?>°</div>
  </div>
  <div class="shad-lagna-item">
    <div class="shad-l-label">வலிமையான கிரகம்</div>
    <div class="shad-l-val" style="color:var(--good);"><?php echo esc_html($summary['strongest'] ?? '-'); ?></div>
  </div>
  <div class="shad-lagna-item">
    <div class="shad-l-label">பலவீன கிரகம்</div>
    <div class="shad-l-val" style="color:var(--bad);"><?php echo esc_html($summary['weakest'] ?? '-'); ?></div>
  </div>
</div>

<!-- SUMMARY BADGES -->
<div class="shad-summary">
  <div class="shad-sum-item">
    <div class="shad-sum-label">வலிமையான கிரகங்கள்</div>
    <div class="shad-sum-val" style="color:var(--good);">
      <?php echo !empty($summary['strong_planets']) ? esc_html(implode(', ', $summary['strong_planets'])) : '—'; ?>
    </div>
  </div>
  <div class="shad-sum-item">
    <div class="shad-sum-label">பலவீன கிரகங்கள்</div>
    <div class="shad-sum-val" style="color:var(--bad);">
      <?php echo !empty($summary['weak_planets']) ? esc_html(implode(', ', $summary['weak_planets'])) : '—'; ?>
    </div>
  </div>
  <div class="shad-sum-item">
    <div class="shad-sum-label">மொத்த கிரகங்கள்</div>
    <div class="shad-sum-val"><?php echo count($shadbala); ?></div>
  </div>
  <div class="shad-sum-item">
    <div class="shad-sum-label">Analysis</div>
    <div class="shad-sum-val">ஷட்பலம் (6 பலங்கள்)</div>
  </div>
</div>

<!-- DETAILED TABLE -->
<div class="shad-section-head">
  <span class="shad-section-dot"></span>
  <span class="shad-section-title">விரிவான ஷட்பல அட்டவணை</span>
</div>
<table class="shad-tbl">
  <tr>
    <th class="left">கிரகம்</th>
    <th>ஸ்தான</th>
    <th>திக்</th>
    <th>கால</th>
    <th>சேஷ்ட</th>
    <th>நைசர்கிக</th>
    <th>த்ருக்</th>
    <th>மொத்தம் (ரூபா)</th>
    <th>வலிமை %</th>
    <th>நிலை</th>
  </tr>
  <?php foreach ($shadbala as $p):
    $sb = $p['shadbala'];
    $pct = $sb['strength_pct'];
    $barColor = $pct >= 100 ? '#2E7D32' : ($pct >= 75 ? '#E65100' : '#C62828');
    $gradeClass = $sb['grade'] === 'மிகவும் வலிமை' ? 'shad-vstrong' : ($sb['is_strong'] ? 'shad-good' : ($pct >= 75 ? 'shad-mid' : 'shad-bad'));
  ?>
  <tr>
    <td class="left" style="font-weight:700;">
      <?php echo esc_html($p['tamil']); ?>
      <?php if ($p['retrograde']): ?><span style="font-size:10px;color:var(--mid);margin-left:3px;">(வ)</span><?php endif; ?>
      <div style="font-size:11px;color:var(--muted);font-weight:400;"><?php echo esc_html($p['rasi']); ?> <?php echo esc_html(number_format($p['degree'],1)); ?>°</div>
    </td>
    <td><?php echo esc_html($sb['sthana_bala']); ?></td>
    <td><?php echo esc_html($sb['dig_bala']); ?></td>
    <td><?php echo esc_html($sb['kala_bala']); ?></td>
    <td><?php echo esc_html($sb['cheshta_bala']); ?></td>
    <td><?php echo esc_html(number_format($sb['naisargika_bala'],1)); ?></td>
    <td><?php echo esc_html($sb['drik_bala']); ?></td>
    <td style="font-weight:700;">
      <?php echo esc_html($sb['total_rupas']); ?> ரூ
      <div style="font-size:10px;color:var(--muted);">Min: <?php echo esc_html($sb['min_required']); ?></div>
    </td>
    <td>
      <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
        <div class="shad-bar-wrap">
          <div class="shad-bar-fill" style="width:<?php echo min(100,$pct); ?>%;background:<?php echo $barColor; ?>;"></div>
        </div>
        <span style="font-weight:700;color:<?php echo $barColor; ?>;"><?php echo esc_html($pct); ?>%</span>
      </div>
    </td>
    <td>
      <span class="shad-badge <?php echo $gradeClass; ?>"><?php echo esc_html($sb['grade']); ?></span>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- PLANET CARDS (visual breakdown) -->
<div class="shad-section-head" style="margin-top:0;">
  <span class="shad-section-dot"></span>
  <span class="shad-section-title">கிரக பல விவரம்</span>
</div>
<div class="shad-cards">
  <?php foreach ($shadbala as $p):
    $sb = $p['shadbala'];
    $pct = $sb['strength_pct'];
    $cardColor = $pct >= 100 ? '#2E7D32' : ($pct >= 75 ? '#E65100' : '#C62828');
    $balas = array(
      'ஸ்தான' => array('val' => $sb['sthana_bala'],     'color' => '#1565C0'),
      'திக்'   => array('val' => $sb['dig_bala'],        'color' => '#00838F'),
      'கால'    => array('val' => $sb['kala_bala'],       'color' => '#6A1B9A'),
      'சேஷ்ட' => array('val' => $sb['cheshta_bala'],    'color' => '#E65100'),
      'நைசர்' => array('val' => $sb['naisargika_bala'], 'color' => '#2E7D32'),
      'த்ருக்'=> array('val' => $sb['drik_bala'],       'color' => '#AD1457'),
    );
  ?>
  <div class="shad-card">
    <div class="shad-card-planet">
      <?php echo esc_html($p['tamil']); ?>
      <?php if ($p['retrograde']): ?><span style="font-size:10px;color:var(--mid);"> ↺</span><?php endif; ?>
    </div>
    <div class="shad-card-rasi"><?php echo esc_html($p['rasi']); ?> <?php echo esc_html(number_format($p['degree'],1)); ?>°</div>
    <?php foreach ($balas as $label => $bala):
      $w = min(100, round(($bala['val'] / 60) * 100));
    ?>
    <div class="shad-card-bar">
      <div class="shad-card-bar-label">
        <span><?php echo esc_html($label); ?></span>
        <span style="color:<?php echo $bala['color']; ?>;font-weight:600;"><?php echo esc_html(number_format($bala['val'],0)); ?></span>
      </div>
      <div class="shad-card-bar-track">
        <div class="shad-card-bar-fill" style="width:<?php echo $w; ?>%;background:<?php echo $bala['color']; ?>;"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="shad-card-total">
      <div>
        <div class="shad-card-rupas" style="color:<?php echo $cardColor; ?>;"><?php echo esc_html($sb['total_rupas']); ?> ரூ</div>
        <div class="shad-card-pct" style="color:<?php echo $cardColor; ?>;"><?php echo esc_html($pct); ?>% · <?php echo esc_html($sb['grade']); ?></div>
      </div>
      <div style="font-size:20px;"><?php echo $pct >= 100 ? '💪' : ($pct >= 75 ? '👌' : '⚠️'); ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- EXPLANATION -->
<div class="shad-section-head">
  <span class="shad-section-dot"></span>
  <span class="shad-section-title">ஷட்பல விளக்கம்</span>
</div>
<div style="background:#fff;padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
  <?php
  $explanations = array(
    array('label'=>'ஸ்தான பலம்','en'=>'Sthana Bala','desc'=>'உச்சம், மூலத்திரிகோணம், சொந்த வீட்டில் கிரகத்தின் நிலை வலிமை','color'=>'#1565C0'),
    array('label'=>'திக் பலம்','en'=>'Dig Bala','desc'=>'திசை அடிப்படையில் கிரகத்தின் வலிமை — சூரியன்/சந்திரன் தெற்கில் வலிமை','color'=>'#00838F'),
    array('label'=>'கால பலம்','en'=>'Kala Bala','desc'=>'பகல்/இரவு, வக்கிர/நேர் நிலை அடிப்படையில் கால வலிமை','color'=>'#6A1B9A'),
    array('label'=>'சேஷ்ட பலம்','en'=>'Cheshta Bala','desc'=>'கிரக இயக்க வேகம் — வக்கிர கிரகங்கள் அதிக சேஷ்ட பலம் பெறும்','color'=>'#E65100'),
    array('label'=>'நைசர்கிக பலம்','en'=>'Naisargika Bala','desc'=>'இயற்கை வலிமை — சூரியன் > சந்திரன் > வெள்ளி > வியாழன் > புதன் > செவ்வாய் > சனி','color'=>'#2E7D32'),
    array('label'=>'த்ருக் பலம்','en'=>'Drik Bala','desc'=>'சுப/அசுப கிரக பார்வை அடிப்படையில் வலிமை — சுப பார்வை = +, அசுப = −','color'=>'#AD1457'),
  );
  foreach ($explanations as $ex):
  ?>
  <div style="display:flex;gap:10px;padding:10px;background:#F8FCFF;border-radius:8px;border-left:3px solid <?php echo $ex['color']; ?>;">
    <div>
      <div style="font-size:13px;font-weight:700;color:<?php echo $ex['color']; ?>;"><?php echo esc_html($ex['label']); ?> <span style="font-size:10px;color:var(--muted);">(<?php echo esc_html($ex['en']); ?>)</span></div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?php echo esc_html($ex['desc']); ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<!-- FOOTER -->
<div class="shad-footer">
  <b>Krishnalaya Astro Centre</b> · ஷட்பலம் Analysis · Swiss Ephemeris Precision · Vellore
</div>

</div>
    <?php
    return ob_get_clean();
}
