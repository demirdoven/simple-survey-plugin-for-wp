<?php
/*
Plugin Name: Basit Anket Eklentisi
Plugin URI: https://github.com/demirdoven
Description: Basit Anket Eklentisi
Author: Selman Demirdoven
Version: 1.0.0
Text Domain: orion-anket-dom
Author URI: https://github.com/demirdoven
*/

if ( !defined( 'ABSPATH' ) ){
	exit;
}
if ( ! defined( 'OFFERS_PLG_DIR' ) ) {
	define( 'OFFERS_PLG_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'OFFERS_PLG_URL' ) ) {
	define( 'OFFERS_PLG_URL', plugin_dir_url( __FILE__ ) );
}

function orion_bhs2_front_scripts(){ 
	wp_enqueue_script('j-cookie-script', 'https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.1/js.cookie.min.js', array( 'jquery' ), '1.0.0', 'all'); 
}
add_action('wp_enqueue_scripts', 'orion_bhs2_front_scripts');

function orion_front_footer(){
	echo do_shortcode('[orion_anket]');
	?>
	<script>
	jQuery(function($){
		
		var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
		
		$(document).on('click', '.anket_ac', function(){ $('.anket_modal_overlay').fadeIn('fast'); $('.anket_modal_inner').addClass('acik'); });
		$(document).on('click', '.anket_modal_overlay, a.anket_iptal, .anket_modal_kapat', function(e){ e.preventDefault(); $('.anket_modal_inner').removeClass('acik');$('.anket_modal_overlay').fadeOut('fast');$('.anket_numbers a').removeClass('secili');return false; });
		$(document).on('click', '.anket_numbers a', function(e){ e.preventDefault(); $('.anket_numbers a').removeClass('secili'); $(this).addClass('secili'); return false; });
		
		$(document).on('click', '.anket_gonder', function(e){
			e.preventDefault();
			var coo = Cookies.get('anket');
			
			if(coo && coo=='kullanildi'){ $('.anket_durum span').html('Daha önce oy kullanmışsınız!'); setTimeout(function(){ $('.anket_modal_inner').removeClass('acik'); $('.anket_modal_overlay').fadeOut('fast'); $('.anket_numbers a').removeClass('secili'); $('.anket_buttons').show(); $('.anket_durum span').html(''); }, 2000); }else{
				if( $('.anket_numbers a.secili').length>0 ){

					$('.anket_buttons').hide();
					$('.anket_durum span').html('Veri işleniyor, lütfen bekleyiniz...');

					var val = $('.anket_numbers a.secili').html();

					var data = {
						action: 'anket_ekleme',
						puan: val
					}
					$.post(ajaxUrl, data, function(response){
						if(response && response=='ok'){
							Cookies.set('anket', 'kullanildi', { expires: 7 });
							$('.anket_durum span').html('Oyunuz başarıyla kaydedildi. Teşekkürler.');

							setTimeout(function(){
								$('.anket_modal_inner').removeClass('acik');
								$('.anket_modal_overlay').fadeOut('fast');
								$('.anket_numbers a').removeClass('secili');
								$('.anket_buttons').show();
								$('.anket_durum span').html('');
							}, 2000);

						}
					});
				}
			}
			return false;
		});
		
	});
	</script>
	<?php
}
add_action('wp_footer', 'orion_front_footer');

function anket_ekleme(){
	$puan = $_REQUEST['puan'];
	$anket = get_option('anket');
	if( $anket && !empty($anket) ){
		$anket[] = array( 'time' => time(), 'puan' => $puan );
		$ekle = $anket;
	}else{ $ekle = array( array( 'time' => time(), 'puan' => $puan ) ); }
	$update = update_option('anket', $ekle);
	if($update){echo 'ok';}
	wp_die();
}
add_action('wp_ajax_anket_ekleme', 'anket_ekleme');
add_action('wp_ajax_nopriv_anket_ekleme', 'anket_ekleme');

function anket_menu_cb_page() {
	add_menu_page( 'Anket', 'Anket', 'edit_posts', 'anket','anket_menu_cb' , 'dashicons-slides');
	add_submenu_page('anket', 'Anket Ayarları', 'Anket Ayarları', 'manage_options', 'anket/ayarlar', 'anket_ayarlar_cb');
}
add_action( 'admin_menu', 'anket_menu_cb_page' );

function anket_ayarlar_cb(){
	if( isset($_POST['anket_ayar_kaydet']) ){update_option('anket_logo', $_POST['anket_logo']);}
	?>
	<style>
		ul.anket_ayarlist {
			margin: 3em 0 4em;	
		}
		ul.anket_ayarlist li label {
			display: flex;
			align-items: center;
		}
		ul.anket_ayarlist li label {
			display: flex;
			align-items: center;
		}
		ul.anket_ayarlist li input[type="text"] {
			width: 300px;
			margin: 0 0 0 2em;
		}
	</style>
	<h1>Anket Ayarları</h1>
	<form action="" name="anket_ayar_kaydet" method="post">
		<ul class="anket_ayarlist">
			<li>
				<label>
					<span>Logo</span>
					<input type="text" name="anket_logo" value="<?php if(get_option('anket_logo')){ echo get_option('anket_logo'); } ?>"/>
					<button class="logo_sec button button-primary">Logo Seç</button>
				</label>
			</li>
		</ul>
		<input type="submit" class="button button-secondary" name="anket_ayar_kaydet" value="Kaydet"/>
	</form>
	<script>
	jQuery(function($){
		var meta_image_frame;
		$(document).on('click', '.logo_sec', function(e){
			e.preventDefault();
			if ( meta_image_frame ) {
				meta_image_frame.open();
				return;
			}
			meta_image_frame = wp.media.frames.meta_image_frame = wp.media({ title: 'Görsel Yükle', button: { text:  'Ekle' }, library: { type: 'image' } });
			meta_image_frame.on('select', function(){
				var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
				console.log(media_attachment);
				$('input[name="anket_logo"]').val(media_attachment.url);
			});
			meta_image_frame.open();
		});
	});
	</script>
	<?php
}
function anket_menu_cb(){
	$anket = get_option('anket');
	
	if( isset($_GET['delete_oys']) && $_GET['delete_oys']=='evet' ){
		$delete = delete_option('anket');
		if($delete){
			?>
			<script>
			window.location.href="<?php echo admin_url(); ?>admin.php?page=anket";
			</script>
			<?php
		}
	}
	?>
	<div id="anket_sonuc_sayfa">
		<h1>Anket Sonucu</h1>
			<?php
			if( $anket && !empty($anket) ){
				$anket = array_reverse($anket);
				?>
				<button class="button button-primary" onclick="window.location.href='<?php echo admin_url(); ?>admin.php?page=anket&delete_oys=evet'" id="anket_temizle">Oyları Temizle</button>
				<?php
				$tum_puans = array();
				$top_puan = 0;
				$i = 0;
				foreach($anket as $tek){
					$top_puan = $top_puan+(int)$tek['puan'];
					$i++;
				}
				if( $i!=0 && $top_puan!=0 ){
					$ort = $top_puan/$i;
					echo '<div id="ortalama"><span>Ortalama Puan: '.number_format($ort,2).'</span></div>';
				}
				
				echo '<div style="display: flex; align-items: flex-start;">';
				date_default_timezone_set('Europe/Istanbul');
				echo '<table class="anket_tablo">';
				echo '<thead><th>Verilen Puan</th><th>Tarih</th></thead>';
				echo '<tbody>';
				foreach($anket as $tek){
					echo '<tr>';
					echo '<td>'.$tek['puan'].'</td>';
					echo '<td>'.date('d/m/Y H:i', $tek['time']).'</td>';
					echo '</tr>';
					$tum_puans[] = $tek['puan'];
				}
				echo '</tbody>';
				echo '</table>';
			}else{
				echo '<div>Şu ana kadar hiç puan verilmemiş!</div>';
			}

			$counter = array_count_values($tum_puans);
			ksort($counter);
			if( $counter && !empty($counter) ){
				echo '<table class="anket_counter">';
				echo '<thead><th>Puan</th><th>Kişi</th></thead>';
				echo '<tbody>';
				foreach( $counter as $key=>$value ){
					echo '<tr>';
					echo '<td>'.$key.'</td>';
					echo '<td>'.$value.' kişi</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
				echo '</div>';
			}
			?>
		
		<?php
		
	echo '</div>';
}

function orion_front_render(){
	ob_start();
	?>
	<div id="anket_wrap">
		<div class="anket_ac" style="background: url(<?php echo OFFERS_PLG_URL; ?>/assets/img/anket_button.png);"></div>
		<div class="anket_modal_wrap">
			
			<div class="anket_modal_overlay"></div>
			<div class="anket_modal_inner">
				<div class="anket_modal_kapat"></div>
				<?php
				if( get_option('anket_logo') && get_option('anket_logo')!='' ){
					echo '<img src="'.get_option('anket_logo').'" class="front_logo"/>';
				}
				?>
				<div class="anket_modal_text">Deneyiminize dayanarak Türkiyenin ilk ve tek koçluk derğisini arkadaşınıza veya ailenizden birine tavsiye etme olasılığınız nedir?</div>
				<div class="anket_numbers">
					<?php
					for($i=0; $i<11; $i++){
						echo '<a href="#">'.$i.'</a>';
					}
					?>
				</div>
				<div class="anket_numbers_desc">
					<span>Hiç olası değil</span>
					<span>Nötr</span>
					<span>Son derece olası</span>
				</div>
				<div class="anket_buttons">
					<a href="#" class="anket_iptal">İptal</a>
					<a href="#" class="anket_gonder">Gönder</a>
				</div>
				<div class="anket_durum">
					<span></span>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('orion_anket', 'orion_front_render');

function orion_front_header(){
	?>
	<style>
		.anket_ac {
			position: fixed;
			right: 0;
			bottom: 0;
			width: 70px;
			height: 70px;
			background-size: cover!important;
			background-position: center!important;
			z-index: 99999;
			cursor: pointer;
		}
		.anket_modal_overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			background: #000000a8;
			z-index: 99999;
			cursor: pointer;
		}
		.anket_modal_inner {
			width: 400px;
			max-width: 82%;
			height: 100vh;
			position: fixed;
			top: 0;
			right: -150%;
			background: white;
			z-index: 99999;
			padding: 6em 3em;
			transition: right .3s ease; 
		}
		.anket_modal_inner.acik {
			right: 0;
			transition: right .3s ease; 
		}
		.anket_numbers {
			display: flex;
			justify-content: space-between;
			padding: 10px 15px;
			border: 4px solid #009688;
			border-radius: 10px;
			margin: 2em 0 0;
		}
		.anket_numbers a {
			font-size: 16px;
			line-height: 1;
			color: inherit;
			font-weight: 400;
			transition: all .2s ease;
		}
		.anket_numbers a:hover,
		.secili {
			color: #009688!important;
			transform: scale(1.4);
			transition: all .2s ease;
		}
		.anket_numbers_desc {
			display: flex;
			justify-content: space-between;
			margin: 10px 0 3em;
		}
		.anket_numbers_desc span {
			font-size: 12px;
		}
		a.anket_iptal {
			padding: 6px 14px;
			background: #fff;
			color: #333;
			font-weight: bold;
			border: 2px solid #333;
		}
		a.anket_gonder {
			padding: 6px 14px;
			background: #525252;
			color: #fff;
			font-weight: bold;
			border: 2px solid #525252;
			margin-left: 5px;
		}
		.anket_modal_kapat {
			position: absolute;
			top: 44px;
			right: 20px;
			width: 30px;
			height: 30px;
			z-index: 99999999999;
			cursor: pointer;
		}
		.anket_modal_kapat:before {
			content: "";
			width: 100%;
			height: 4px;
			background: black;
			position: absolute;
			top: 14px;
			left: 0;
			z-index: 999999999;
			transform: rotate(
		45deg
		);
		}
		.anket_modal_kapat:after {
			content: "";
			width: 100%;
			height: 4px;
			background: black;
			position: absolute;
			top: 14px;
			left: 0;
			z-index: 999999999;
			transform: rotate( 
		314deg
		);
		}
		.anket_buttons {
			margin-bottom: 2em;
		}
		.td-scroll-up.td-scroll-up-visible {
			display: none;
		}
		img.front_logo {
			max-height: 100px;
			margin: 0 auto 1em;
			display: block;
		}
	</style>
	<?php
}
add_action('wp_head', 'orion_front_header');

function orion_admin_header(){
	?>
	<style>
		table.anket_tablo thead th,
		table.anket_counter thead th {
			background: #333;
			color: #fff;
			padding: 4px 10px;
			text-align: center;
		}
		table.anket_tablo tbody td,
		table.anket_counter tbody td {
			background: #fff;
			padding: 4px 10px;
			font-weight: normal;
			font-size: 16px;
			text-align: center;
		}
		table.anket_tablo tbody td:first-child,
		table.anket_counter tbody td:first-child {
			font-weight: bold;
		}
		div#ortalama {
			margin-bottom: 8px;
		}
		div#ortalama span {
			background: #009688;
			color: #fff;
			font-size: 20px;
			padding: 6px 10px 8px;
			line-height: 1;
			display: inline-block;
			border-radius: 4px;
		}
		div#anket_sonuc_sayfa h1 {
			display: inline-block;
		}
		button#anket_temizle {
			margin: .9em 10px 3em;
		}
		table.anket_counter {
			margin-left: 2em;
		}
	</style>
	<?php
}
add_action('admin_head', 'orion_admin_header');

?>