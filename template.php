<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Показываем сообщения из GET параметров
if ($_GET['success'] == 'Y') {
    $arResult['DATA_SAVED'] = 'Y';
}
if ($_GET['error']) {
    $arResult["strProfileError"] = htmlspecialcharsbx($_GET['error']);
}

// Если arResult не заполнен, получаем данные текущего пользователя
if (!isset($arResult["arUser"]) || !$arResult["arUser"]) {
    global $USER;
    $userID = $USER->GetID();
    if ($userID) {
        $rsUser = CUser::GetByID($userID);
        if ($arUser = $rsUser->Fetch()) {
            $arResult["arUser"] = $arUser;
            $arResult["ID"] = $userID;
        }
    }
}

// Устанавливаем PHONE_REGISTRATION в true если не задано
if (!isset($arResult['PHONE_REGISTRATION'])) {
    $arResult['PHONE_REGISTRATION'] = true;
}

if($arResult['SHOW_SMS_FIELD'] && !$arResult["strProfileError"]){
	CJSCore::Init('phone_auth');
}

global $arTheme;

// get phone auth params - с проверкой на существование класса
$bPhoneAuthSupported = false;
$bPhoneAuthShow = false;
$bPhoneAuthRequired = false;
$bPhoneAuthUse = false;

if(class_exists('Aspro\Next\PhoneAuth')) {
    list($bPhoneAuthSupported, $bPhoneAuthShow, $bPhoneAuthRequired, $bPhoneAuthUse) = Aspro\Next\PhoneAuth::getOptions();
}
?>
<div class="module-form-block-wr lk-page border_block">
	<?if($arResult["strProfileError"]):?>
		<div class="alert alert-danger"><?=$arResult["strProfileError"]?></div>
	<?endif;?>
	<?if($arResult['DATA_SAVED'] === 'Y'):?>
		<div class="alert alert-success"><?=GetMessage('PROFILE_DATA_SAVED')?></div>
	<?endif;?>
	<?if($arResult["SHOW_SMS_FIELD"] && !$arResult["strProfileError"]):?>
		<div class="alert alert-success"><?=GetMessage('main_profile_code_sent')?></div>
	<?endif;?>
	<div class="form-block-wr">
		<?if($arResult["SHOW_SMS_FIELD"] && !$arResult["strProfileError"]):?>
			<form method="post" name="form1" class="main" action="/local/ajax/profile_handler.php" enctype="multipart/form-data">
				<?=bitrix_sessid_post()?>
				<input type="hidden" name="lang" value="<?=LANG?>" />
				<input type="hidden" name="ID" value="<?=$arResult["ID"]?>" />
				<input type="hidden" name="return_url" value="<?=$APPLICATION->GetCurPage()?>" />
				<input type="hidden" name="save" value="Y" />
				<input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult["SIGNED_DATA"])?>" />
				<div class="form-control">
					<div class="wrap_md">
						<div class="iblock label_block">
							<label><?=GetMessage("main_profile_code")?><span class="star">*</span></label>
							<input size="30" type="text" name="SMS_CODE" value="<?=htmlspecialcharsbx($arResult["SMS_CODE"])?>" autocomplete="off" />
						</div>
					</div>
				</div>
				<div class="but-r">
					<button class="btn btn-default" type="submit" name="code_submit_button" value="Y"><span><?=GetMessage("main_profile_send")?></span></button>
				</div>
				<div id="bx_profile_error" style="display:none"><?ShowError("error")?></div>
				<div id="bx_profile_resend"></div>
				<script>
				new BX.PhoneAuth({
					containerId: 'bx_profile_resend',
					errorContainerId: 'bx_profile_error',
					interval: <?=$arResult["PHONE_CODE_RESEND_INTERVAL"]?>,
					data:
						<?=CUtil::PhpToJSObject([
							'signedData' => $arResult["SIGNED_DATA"],
						])?>,
					onError:
						function(response)
						{
							var errorDiv = BX('bx_profile_error');
							var errorNode = BX.findChildByClassName(errorDiv, 'errortext');
							errorNode.innerHTML = '';
							for(var i = 0; i < response.errors.length; i++)
							{
								errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
							}
							errorDiv.style.display = '';
						}
				});
				</script>
			</form>
		<?else:?>
			<form method="post" name="form1" class="main" action="<?=$arResult["FORM_TARGET"]?>?" enctype="multipart/form-data">
				<?=$arResult["BX_SESSION_CHECK"]?>
				<input type="hidden" name="lang" value="<?=LANG?>" />
				<input type="hidden" name="ID" value=<?=$arResult["ID"]?> />
				<?if($arTheme["LOGIN_EQUAL_EMAIL"]["VALUE"] == "Y"):?>
					<input type="hidden" name="LOGIN" maxlength="50" value="<? echo $arResult["arUser"]["LOGIN"]?>" />
				<?else:?>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_LOGIN")?><span class="star">*</span></label>
								<input required type="text" name="LOGIN" required value="<?=$arResult["arUser"]["LOGIN"]?>" />
							</div>
						</div>
					</div>
				<?endif;?>
				<?if($arTheme["PERSONAL_ONEFIO"]["VALUE"] == "Y"):?>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_FIO")?><span class="star">*</span></label>
								<?
								$arName = array();
								if(!$arResult["strProfileError"])
								{
									if($arResult["arUser"]["LAST_NAME"]){
										$arName[] = $arResult["arUser"]["LAST_NAME"];
									}
									if($arResult["arUser"]["NAME"]){
										$arName[] = $arResult["arUser"]["NAME"];
									}
									if($arResult["arUser"]["SECOND_NAME"]){
										$arName[] = $arResult["arUser"]["SECOND_NAME"];
									}
								}
								else
									$arName[] = htmlspecialcharsbx($_POST["NAME"]);
								?>
								<input required type="text" name="NAME" maxlength="50" value="<?=implode(' ', $arName);?>" />
							</div>
							<div class="iblock text_block">
								<?=GetMessage("PERSONAL_NAME_DESCRIPTION")?>
							</div>
						</div>
					</div>
				<?else:?>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_LASTNAME")?></label>
								<input type="text" name="LAST_NAME" maxlength="50" value="<?=$arResult["arUser"]["LAST_NAME"];?>" />
							</div>
						</div>
					</div>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_NAME")?></label>
								<input type="text" name="NAME" maxlength="50" value="<?=$arResult["arUser"]["NAME"];?>" />
							</div>
						</div>
					</div>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_SECONDNAME")?></label>
								<input type="text" name="SECOND_NAME" maxlength="50" value="<?=$arResult["arUser"]["SECOND_NAME"];?>" />
							</div>
						</div>
					</div>
				<?endif;?>
				<div class="form-control">
					<div class="wrap_md">
						<div class="iblock label_block">
							<label><?=GetMessage("PERSONAL_EMAIL")?><span class="star">*</span></label>
							<input required type="text" name="EMAIL" maxlength="50" placeholder="name@company.ru" value="<? echo $arResult["arUser"]["EMAIL"]?>" />
						</div>
						<div class="iblock text_block">
							<?if($arTheme["LOGIN_EQUAL_EMAIL"]["VALUE"] != "Y"):?>
								<?=GetMessage("PERSONAL_EMAIL_SHORT_DESCRIPTION")?>
							<?else:?>
								<?=GetMessage("PERSONAL_EMAIL_DESCRIPTION")?>
							<?endif;?>
						</div>
					</div>
				</div>
				<?$mask = \Bitrix\Main\Config\Option::get('aspro.next', 'PHONE_MASK', '+7 (999) 999-99-99');?>
				
				<!-- ОСНОВНОЙ ТЕЛЕФОН ДЛЯ SMS -->
				<?if($arResult['PHONE_REGISTRATION']):?>
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("main_profile_phone_number")?><span class="star">*</span></label>
								<?
								// Берем номер из PHONE_NUMBER или PERSONAL_PHONE (что заполнено)
								$phoneValue = $arResult["arUser"]["PHONE_NUMBER"];
								if(empty($phoneValue)) {
									$phoneValue = $arResult["arUser"]["PERSONAL_PHONE"];
								}
								
								// Добавляем + если нужно
								if(strlen($phoneValue) && strpos($phoneValue, '+') === false && strpos($mask, '+') !== false){
									$phoneValue = '+'.$phoneValue;
								}
								?>
								<input required type="tel" name="PHONE_NUMBER" class="phone" maxlength="255" value="<?=$phoneValue?>" />
							</div>
							<div class="iblock text_block">
								<?=GetMessage("PHONE_NUMBER_DESCRIPTION".($bPhoneAuthUse ? '_WITH_AUTH' : ''))?>
							</div>
						</div>
					</div>

					<?
					// НОВАЯ ЛОГИКА: Проверяем идентичность номеров
					$phoneNumber = $arResult["arUser"]["PHONE_NUMBER"];
					$personalPhone = $arResult["arUser"]["PERSONAL_PHONE"];
					
					// Нормализуем номера для сравнения (убираем пробелы, скобки и т.д.)
					$normalizePhone = function($phone) {
						return preg_replace('/[^\d+]/', '', $phone);
					};
					
					$normalizedPhoneNumber = $normalizePhone($phoneNumber);
					$normalizedPersonalPhone = $normalizePhone($personalPhone);
					
					// Определяем логику показа
					$phonesAreIdentical = (!empty($normalizedPhoneNumber) && !empty($normalizedPersonalPhone) && 
					                     $normalizedPhoneNumber === $normalizedPersonalPhone);
					
					$hasOnlyOnePhone = (!empty($phoneNumber) && empty($personalPhone)) || 
					                  (!empty($personalPhone) && empty($phoneNumber));
					                  
					$showAddButton = $phonesAreIdentical || $hasOnlyOnePhone || (empty($phoneNumber) && empty($personalPhone));
					
					// Если номера идентичны или есть только один номер, показываем кнопку добавления
					if($showAddButton):?>
						<!-- Показываем кнопку добавления -->
						<div class="form-control" id="add-phone-button">
							<a href="#" onclick="showAdditionalPhone(); return false;" class="btn-link">
								+ <?=$phonesAreIdentical ? 'Изменить дополнительный номер' : 'Добавить дополнительный номер'?>
							</a>
						</div>
						
						<!-- Скрытое поле для дополнительного телефона -->
						<div class="form-control" id="additional-phone-field" style="display: none;">
							<div class="wrap_md">
								<div class="iblock label_block">
									<label><?=GetMessage("PERSONAL_PHONE")?></label>
									<input type="tel" name="PERSONAL_PHONE" class="phone" maxlength="255" value="" placeholder="Введите дополнительный номер" />
								</div>
								<div class="iblock text_block">
									<?=GetMessage("PERSONAL_PHONE_DESCRIPTION")?>
									<br><a href="#" onclick="hideAdditionalPhone(); return false;" class="btn-link-sm">Отменить</a>
								</div>
							</div>
						</div>
						
						<!-- Скрытое поле для сохранения факта идентичности -->
						<input type="hidden" name="PHONES_WERE_IDENTICAL" value="<?=$phonesAreIdentical ? 'Y' : 'N'?>" />
					<?else:?>
						<!-- Показываем дополнительный телефон если номера разные -->
						<?
						$additionalPhoneValue = $personalPhone;
						// Добавляем + если нужно
						if(strlen($additionalPhoneValue) && strpos($additionalPhoneValue, '+') === false && strpos($mask, '+') !== false){
							$additionalPhoneValue = '+'.$additionalPhoneValue;
						}
						?>
						<div class="form-control" id="additional-phone-field">
							<div class="wrap_md">
								<div class="iblock label_block">
									<label><?=GetMessage("PERSONAL_PHONE")?></label>
									<input type="tel" name="PERSONAL_PHONE" class="phone" maxlength="255" value="<?=$additionalPhoneValue?>" />
								</div>
								<div class="iblock text_block">
									<?=GetMessage("PERSONAL_PHONE_DESCRIPTION")?>
									<br><a href="#" onclick="clearAdditionalPhone(); return false;" class="btn-link-sm">Удалить дополнительный номер</a>
								</div>
							</div>
						</div>
					<?endif;?>
				<?else:?>
					<!-- Если SMS регистрация отключена, показываем обычный телефон -->
					<div class="form-control">
						<div class="wrap_md">
							<div class="iblock label_block">
								<label><?=GetMessage("PERSONAL_PHONE")?><span class="star">*</span></label>
								<?
								if(strlen($arResult["arUser"]["PERSONAL_PHONE"]) && strpos($arResult["arUser"]["PERSONAL_PHONE"], '+') === false && strpos($mask, '+') !== false){
									$arResult["arUser"]["PERSONAL_PHONE"] = '+'.$arResult["arUser"]["PERSONAL_PHONE"];
								}
								?>
								<input required type="tel" name="PERSONAL_PHONE" class="phone" maxlength="255" value="<?=$arResult["arUser"]["PERSONAL_PHONE"]?>" />
							</div>
							<div class="iblock text_block">
								<?=GetMessage("PERSONAL_PHONE_DESCRIPTION")?>
							</div>
						</div>
					</div>
				<?endif;?>
				
				<div class="form-control wrapper-required-text">
					<?$APPLICATION->IncludeFile(SITE_DIR."include/required_message.php", Array(), Array("MODE" => "html"));?>
				</div>
				<div class="but-r">
					<button class="btn btn-default" type="submit" name="save" value="Y"><span><?=(($arResult["ID"]>0) ? GetMessage("MAIN_SAVE_TITLE") : GetMessage("MAIN_ADD_TITLE"))?></span></button>
				</div>
			</form>
			<?if($arResult["SOCSERV_ENABLED"]){ $APPLICATION->IncludeComponent("bitrix:socserv.auth.split", "main", array("SUFFIX"=>"form", "SHOW_PROFILES" => "Y","ALLOW_DELETE" => "Y"),false);}?>
		<?endif;?>
	</div>
	<script>
	$(document).ready(function(){
		$(".form-block-wr form").validate({rules:{ EMAIL: { email: true }}});
		
		// Принудительно устанавливаем правильный action
		$('form[name="form1"]').attr('action', '/local/ajax/profile_handler.php');
		
		// Инициализируем интерфейс при загрузке страницы (с небольшой задержкой)
		setTimeout(function() {
			updatePhoneInterface();
		}, 100);
		
		// AJAX отправка формы
		$('form[name="form1"]').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $submitBtn = $form.find('button[type="submit"]');
			var originalText = $submitBtn.find('span').text();
			
			// Блокируем кнопку
			$submitBtn.prop('disabled', true).find('span').text('Сохранение...');
			
			// Убираем предыдущие сообщения
			$('.alert').remove();
			
			console.log('Отправляем форму на:', $form.attr('action'));
			var formData = $form.serialize();
			console.log('Данные формы:', formData);
			console.log('Данные формы (объект):', $form.serializeArray());
			
			// Проверяем что action правильный
			if ($form.attr('action').indexOf('/local/ajax/profile_handler.php') === -1) {
				console.log('ВНИМАНИЕ: Неправильный action, исправляем...');
				$form.attr('action', '/local/ajax/profile_handler.php');
			}
			
			$.ajax({
				url: '/local/ajax/profile_handler.php',
				type: 'POST',
				data: $form.serialize() + '&save=Y',
				success: function(response) {
					console.log('Сырой ответ сервера:', response);
					
					// Пробуем парсить JSON
					var jsonResponse;
					try {
						jsonResponse = typeof response === 'string' ? JSON.parse(response) : response;
					} catch (e) {
						console.log('Ошибка парсинга JSON:', e);
						$('.module-form-block-wr').prepend(
							'<div class="alert alert-danger">Ошибка формата ответа сервера</div>'
						);
						return;
					}
					
					console.log('Распарсенный ответ:', jsonResponse);
					
					if (jsonResponse.success) {
						// Показываем сообщение об успехе
						$('.module-form-block-wr').prepend(
							'<div class="alert alert-success">Изменения сохранены</div>'
						);
						
						// Обновляем интерфейс после сохранения
						updatePhoneInterface();
						
						// Обновляем данные в форме если нужно
						if (jsonResponse.data) {
							$.each(jsonResponse.data, function(key, value) {
								$form.find('[name="' + key + '"]').val(value);
							});
						}
					} else {
						// Показываем ошибки
						var errorText = jsonResponse.message || 'Произошла ошибка';
						if (jsonResponse.messages && jsonResponse.messages.length > 0) {
							errorText = jsonResponse.messages.join('<br>');
						}
						$('.module-form-block-wr').prepend(
							'<div class="alert alert-danger">' + errorText + '</div>'
						);
					}
				},
				error: function(xhr, status, error) {
					console.log('Ошибка AJAX:', {xhr: xhr, status: status, error: error});
					console.log('Ответ сервера:', xhr.responseText);
					
					var errorMessage = 'Ошибка соединения с сервером';
					if (xhr.responseText) {
						try {
							var response = JSON.parse(xhr.responseText);
							if (response.message) {
								errorMessage = response.message;
							}
						} catch (e) {
							errorMessage = 'Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText;
						}
					}
					
					$('.module-form-block-wr').prepend(
						'<div class="alert alert-danger">' + errorMessage + '</div>'
					);
				},
				complete: function() {
					// Разблокируем кнопку
					$submitBtn.prop('disabled', false).find('span').text(originalText);
					
					// Скроллим к началу для показа сообщения
					$('html, body').animate({
						scrollTop: $('.module-form-block-wr').offset().top - 20
					}, 500);
				}
			});
		});
		
		// Убираем старую синхронизацию - теперь обрабатывается по-новому
	});
	
	function normalizePhone(phone) {
		if (!phone) return '';
		// Убираем все кроме цифр (включая +, скобки, пробелы, дефисы)
		var normalized = phone.replace(/[^\d]/g, '');
		// Убираем ведущие нули
		normalized = normalized.replace(/^0+/, '');
		return normalized;
	}
	
	function showAdditionalPhone() {
		$('#add-phone-button').hide();
		$('#additional-phone-field').show();
		// Фокус на поле ввода
		$('#additional-phone-field input[name="PERSONAL_PHONE"]').focus();
	}
	
	function hideAdditionalPhone() {
		$('#add-phone-button').show();
		$('#additional-phone-field').hide();
		// Очищаем поле
		$('#additional-phone-field input[name="PERSONAL_PHONE"]').val('');
	}
	
	function clearAdditionalPhone() {
		if(confirm('Удалить дополнительный номер телефона?')) {
			var $personalPhoneInput = $('#additional-phone-field input[name="PERSONAL_PHONE"]');
			$personalPhoneInput.val('');
			// Перестраиваем интерфейс - это покажет кнопку "Добавить номер"
			updatePhoneInterface();
		}
	}
	
	// Функция для обновления интерфейса после изменений
	function updatePhoneInterface() {
		console.log('updatePhoneInterface() вызвана');
		
		var $phoneNumberInput = $('input[name="PHONE_NUMBER"]');
		var $personalPhoneInput = $('input[name="PERSONAL_PHONE"]');
		var $addButton = $('#add-phone-button');
		var $additionalField = $('#additional-phone-field');
		
		console.log('Найденные элементы:', {
			phoneInput: $phoneNumberInput.length,
			personalInput: $personalPhoneInput.length, 
			addButton: $addButton.length,
			additionalField: $additionalField.length
		});
		
		// Проверяем что основные элементы существуют
		if (!$addButton.length || !$additionalField.length) {
			console.log('Основные элементы интерфейса телефонов не найдены, попробуем через 500мс');
			setTimeout(updatePhoneInterface, 500);
			return;
		}
		
		var phoneNumber = normalizePhone($phoneNumberInput.val() || '');
		var personalPhone = normalizePhone($personalPhoneInput.val() || '');
		
		console.log('updatePhoneInterface данные:', {phoneNumber, personalPhone});
		
		// НОВАЯ ЛОГИКА: Если дополнительный номер пустой или идентичен основному - показываем кнопку добавления
		if (!personalPhone || phoneNumber === personalPhone) {
			console.log('Скрываем дополнительное поле, показываем кнопку');
			$additionalField.hide();
			$addButton.show();
			// Очищаем поле дополнительного номера если он идентичен основному
			if (phoneNumber === personalPhone && personalPhone) {
				console.log('Очищаем идентичный номер');
				$personalPhoneInput.val('');
			}
		} else {
			console.log('Показываем дополнительное поле, скрываем кнопку');
			// Показываем поле с дополнительным номером только если он реально отличается
			$addButton.hide();
			$additionalField.show();
		}
	}
	
	// Отслеживаем изменения в основном номере для обновления интерфейса
	$(document).on('input', 'input[name="PHONE_NUMBER"]', function() {
		// Небольшая задержка для корректной работы
		setTimeout(updatePhoneInterface, 100);
	});
	</script>
</div>