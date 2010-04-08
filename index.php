<?php
function get_mail($domain, &$errors, $filename) {
  preg_match('/^(http:\/\/|www\.)*([a-zA-Z0-9-]*\.([\.a-zA-Z0-9]*))/', $domain, $res);
  $zone = $res[3];
  $url = $res[2];
  
  switch ($zone) {
    case 'com':
      return exec('whois '.$url.' | grep "Registrant:" -A 3 | grep @ | sed -r "s/.*\(//g" | sed "s/)//g"');
    break;

    case 'org':
      return exec('whois '.$url.' | grep "Registrant Email:" | sed "s/Registrant Email://g"');
    break;
    
    case 'net':
      return exec('whois '.$url.' | grep @  | sed -r "s/(.)*[^a-zA-Z0-9\_\.\@-]//g"');
    break;
    
    case 'ru':
      while (file_exists('data/ru_su.lock')) {
        sleep(2);
      }
      exec('touch data/ru_su.lock');
      $mail = exec('whois '.$url.' | grep e-mail | sed "s/e-mail:     //g"');
      sleep(2);
      exec('rm data/ru_su.lock');
      return $mail;
      break;
    
    case 'su':
      while (file_exists('data/ru_su.lock')) {
        sleep(2);
      }
      exec('touch data/ru_su.lock');
      $mail = exec('whois '.$url.' | grep e-mail | sed "s/e-mail:     //g"');
      sleep(2);
      exec('rm data/ru_su.lock');
      return $mail;
    break;
    
    case 'info':
      return exec('whois '.$url.' | grep "Registrant Email:" | sed "s/Registrant Email://g"');
    break;

    case 'biz':
      return exec('whois '.$url.' | grep "Registrant Email:" | sed "s/Registrant Email:                            //g"');
    break;
    
    case 'kiev.ua':
      return exec('whois '.$url.' | grep "% Administrative Contact:" -A 15 | grep "e-mail:" | sed "s/e-mail:         //g"');
    break;
    
    case 'com.ua':
      return exec('whois '.$url.' | grep "% Administrative Contact:" -A 15 | grep "e-mail:" | sed "s/e-mail:         //g"');
    break;
    
    case 'ua':
      return exec('whois '.$url.' | grep "% Administrative Contact:" -A 15 | grep "e-mail:" | sed "s/e-mail:         //g"');
    break;
    
    default:
      $errors['domains'][] = $domain;
      $errors['zones'][$zone]++;
      return '';
   	break;
   }
}


if (isset($_POST['domains'])) {
  $domains = explode("\n", $_POST['domains']);
  
  echo '<table border="1">';
  $filename = $_POST['filename'];
  $all = count($domains);
  foreach ($domains as $num => $domain) {
    $dom = trim($domain);
    if ($dom) {
      $mail = get_mail($dom, $errors, $filename);
      if ($mail) {
        echo '<tr><td>'.$dom.'</td><td>'.$mail.'</td></tr>';
        exec('echo "'.$dom.','.$mail.'" >> data/'.$filename.'.csv');
        exec('echo "Выполняется..... (Обрабатывается '.($num + 1).'/'.$all.')" > data/'.$filename.'_status.txt');
      }
    }
  }
  echo '</table><br>';
  echo '<b>Всего было получено '.($num + 1).' строк с доменами (тут учитываются и пустые строки если они там были)</b>';
  exec('echo "Всего было получено '.($num + 1).' доменов" > data/'.$filename.'_status.txt');
  exec('echo "Список email адресов вот тут:  http://'.$_SERVER['HTTP_HOST'].str_replace('/index.php', '/data/'.$filename.'.csv', $_SERVER['PHP_SELF']).'" >> data/'.$filename.'_status.txt');
  
  if (!empty($errors)) {
    echo '<hr><h2>'.count($errors['domains']).' домен(ов) не обработано</h2>';
    exec('echo "Не обработано: '.count($errors['domains']).' домен(ов) из следующих доменных зон" >> data/'.$filename.'_status.txt');
    echo '<table border="1" style="background: #fbb">';
    echo '<tr><th>Названия доменной зоны</th><th>Число необработанных доменов<br> в этой зоне</th></tr>';
    foreach ($errors['zones'] as $zone => $domain_numbers) {
      echo '<tr><td>'.$zone.'</td><td>'.$domain_numbers.'</td>';
      exec('echo "'.$zone.'...'.$domain_numbers.'" >> data/'.$filename.'_status.txt');
    }
    echo '</table>';
    
    echo 'Ниже приводится весь список необработанных доменов. <br><textarea rows="10" cols="60" style="background: #fbb">';
    exec('printf "\nНиже приводится весь список необработанных доменов:\n" >> data/'.$filename.'_status.txt');
    foreach ($errors['domains'] as $num => $error) {
      echo $error."\n";
      exec('echo "'.$error.'" >> data/'.$filename.'_status.txt');
    }
    echo '</textarea>';
  }
  else {
    echo 'И все домены удалось успешно обработать';
  }
  
  
}
else {
  $filename = uniqid();
  print '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'">';
  print 'Введите список доменов, к которым надо получить email адреса. По одному домену в каждой строке. Можно с http:// www  и со всяким прочим шлаком<br>';
  print '<textarea rows="35" cols="130" name="domains"></textarea><br><br>';
  exec('touch data/'.$filename.'_status.txt');
  print 'Если доменов много, то время получения списка всех почтовых адресов может быть длительным. Но тебе не обязательно держать открытым всё время окно выполнения скрипта. Его можно будет закруть после сабмита форму недожидаясь загрузки. <b>Но</b> предварительно просто открой вот эту ссылку: <a href="http://'.$_SERVER['HTTP_HOST'].str_replace('/index.php', '/data/'.$filename.'_status.txt', $_SERVER['PHP_SELF']).'">http://'.$_SERVER['HTTP_HOST'].str_replace('/index.php', '/data/'.$filename.'_status.txt', $_SERVER['PHP_SELF']).'</a>  По этой ссылке ты можешь видеть результат выполнения скрипта, а по окончанию выполнения получишь ссылку на список emailов';
  print '<br><br><input type="submit"><br>';
  print '<input type="hidden" name="filename" value='.$filename.'>';
  print '</form>';
  print 'Скан идёт по следующим доменным зонам: <b>com, org, net, ru, su, info, biz, kiev.ua, com.ua, ua</b>, остальные домены просто игнорятся.';
}