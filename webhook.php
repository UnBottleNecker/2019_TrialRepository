<?php
# ログファイル定義
$LOG_FILE = dirname(__FILE__).'/_hook.log/hook.log';
# エラーログファイル定義
$LOG_FILE_ERR = dirname(__FILE__).'/_hook.log/hook-error.log';
# GitHubに設定するSecret
$SECRET_KEY = 'xxxxx';
# git pullしたいブランチ 例では「master」のブランチを設定
$BRANCHS = array('master');

# 全てのHTTPリクエストヘッダを取得
$header = getallheaders();

# POSTの生データを取得
$post_data = file_get_contents( 'php://input' );

# ハッシュ値を生成
$hmac = hash_hmac('sha1', $post_data, $SECRET_KEY);

if ( isset($header['X-Hub-Signature']) && $header['X-Hub-Signature'] === 'sha1='.$hmac ) {
  $payload = json_decode($post_data, true);

  foreach ($BRANCHS as $branch) {
    if($payload['ref'] == 'refs/heads/'.$branch){
      chdir($payload['repository']['name'].'/');

      exec('git pull origin '.$branch.' 2>&1', $output, $return);

      file_put_contents($LOG_FILE,
        date("[Y-m-d H:i:s]")." ".
        $_SERVER['REMOTE_ADDR']." ".
        $payload['repository']['name']."/".$branch." ".
        $payload['commits'][0]['message']." ".
        $output[0]." ".$return."\n",
        FILE_APPEND|LOCK_EX
      );
    }
  }

} else {
  file_put_contents($LOG_FILE_ERR,
    date("[Y-m-d H:i:s]")." ".
    $_SERVER['REMOTE_ADDR']." 認証失敗"."\n",
    FILE_APPEND|LOCK_EX
  );
}
?>