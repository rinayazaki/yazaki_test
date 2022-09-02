<?php

class ReadReceipt
{
    private $file_path;
    public function __construct()
    {
        $this->file_path = '/Users/yazakirina/Downloads/FA選考課題/';
    }

    public function get_files(){
        $files = glob($this->file_path.'*.jpg');
        return $files;
    }

    public function get_result($file){
        try{
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.fastaccounting.jp/v1.5/receipt',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file'=> new CURLFILE("$file")),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmYXN0YWNjb3VudGluZy5hcGkudG9rZW4iLCJhdWQiOiJmYXN0YWNjb3VudGluZy5hcGkudG9rZW4iLCJqdGkiOiIzZmY2MWY3MmUxY2M5NTA2NjYyMzExNjAxYjJkNTVjOTUyZDE1YTM4MmYzNjlkN2Q3MDM4Y2E5OWFmNzExYTRhIiwiaWF0IjoxNjM2NTE2NzIxLCJpZGVudGlmaWVyIjoxNTQwfQ.8z5qAd00NJEk36ZHiOANxCsmaHxbUQOosz97_XnjJEY'
            ),
            ));

            $response = json_decode(curl_exec($curl));
            curl_close($curl);
            
            return $response;
        }catch(Exception $ex){
            return $ex->getMessage();
        }
    }

    public function csv_export($header, $receipt_data){         
        $fp = fopen('data.csv', 'w');
        $file_path = $this->file_path;
        // ヘッダー書き出し
        foreach ($header as $head) {
            fputcsv($fp, $head);
        }

        $lines = [];
        foreach ($receipt_data as $file_name=>$data) {
            $lines[] =
            [
                preg_replace('#'.$file_path.'#', '', $file_name),
                $data->date ?? '',
                $data->amount ?? '',
                $data->issuer ?? '',
                $data->tel ?? '',
                $data->options->confidences->date ?? '',
                $data->options->confidences->amount ?? '',
                $data->options->confidences->tel ?? '',
                $data->options->amount_verification ?? '',
            ];
        }

        // データ書き出し
        foreach($lines as $line){
            fputcsv($fp, $line);
        }

        // ファイルを閉じる
        fclose($fp);
        $objDateTime = new DateTime();
        $filename = "fa_receipt_".$objDateTime->format('Ymd');
        $file = "data.csv";
      
        // ダウンロードするダイアログを出力
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=$filename.csv");
        
        // ファイルを読み込んで出力
        ob_end_clean(); 
        readfile($file);
      
        unlink($file);
    }

}

$obj = new ReadReceipt();
$files = $obj->get_files();

$receipt_datas = [];
foreach($files as $file){
    $receipt_data[$file] = $obj->get_result($file);
}

$header[] =
[
    '画像ファイル名',
    '日付',
    '合計金額',
    '会社名',
    '電話番号',
    '日付の読み取り結果信頼度',
    '合計金額の読取結果信頼度',
    '電話番号の読取結果信頼度',
    '合計金額の確認結果'
];

$obj->csv_export($header, $receipt_data);

// echo '<pre>';
// var_dump($receipt_data);
// echo '</pre>';

?>