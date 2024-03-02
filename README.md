# CodeIgniter 4 Application Starter

# how to pembuatan api mudah

buat dbnya dulu di mysql namanya

        php spark make:model nama-dbbebas

        php spark make:migration sama

setelah di buat migrationnya
php spark migrate
maka akan otomatis memberikan tabel di mysql

jika ingin memperbarui lagi ketik
php spark migrate:refresh

# membuat data untuk view

        php spark make:controller PegawaiController -restful

edit pada controllers
buat
protected $modelName = 'App\models\pegawai';
protected $format ='json';
lalu untuk menampilkan data ketik ini

$data = [
'message' => 'sukses',
'data_pegawai' => $this->model->findAll()
];

return $this->respond($data, 200);

buka api response trait
