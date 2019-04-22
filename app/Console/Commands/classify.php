<?php

namespace App\Console\Commands;

use App\Configs;
use App\Http\Controllers\ImportController;
use App\ModelFunctions\AlbumFunctions;
use App\ModelFunctions\Helpers;
use App\ModelFunctions\PhotoFunctions;
use App\Photo;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;
use Illuminate\Support\Facades\Config;

class import extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'lychee:classify {script : classify.py script}  {--album_id= : Album ID to import to}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Classifies all photos in an album using a classify script';

	/**
	 * @var AlbumFunctions
	 */
	private $albumFunctions;

	/**
	 * @var PhotoFunctions
	 */
	private $photoFunctions;

	/**
	 * Create a new command instance.
	 *
	 * @param PhotoFunctions $photoFunctions
	 * @param AlbumFunctions $albumFunctions
	 * @return void
	 */
	public function __construct(PhotoFunctions $photoFunctions, AlbumFunctions $albumFunctions)
	{
		parent::__construct();

		$this->photoFunctions = $photoFunctions;
		$this->albumFunctions = $albumFunctions;
	}


	public function classifyImages($script, $photos) {
		$input = new InputStream();
		$args = ['./'.$script];
		foreach ($photos as $photo) {
			$path = $photo['url'];
			$url = Config::get('defines.dirs.LYCHEE_UPLOADS_BIG').$path;
			array_push($args, $url);
		}
		$process = new Process($args);
		$process->setTimeout(3600);
		$process->run();
		$output = $process->getOutput();
		$lines = explode("\n", $output);
		foreach($lines as $line) {
			if (!strpos($line, "|")) {
				$this->line('skip '.$line);
				continue;
			}
			$exploded = explode('|', $line);
			if (count($exploded) != 3) {
				$this->error('Wrong count: '.$line);
			}
			list($img_path, $desc, $prob) = $exploded;
			$this->line('Got '.$img_path.': '.$desc.' (prob='.$prob.')');
			$path_explode = explode('/', $img_path);
			$url = $path_explode[count($path_explode)-1];
			$photo = Photo::where('url', $url)->first();
			$photo->tags = $desc;
			$photo->save();
		}
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$script = $this->argument('script');
		$album_id = $this->option('album_id');
		$owner_id = 0; // $this->option('owner_id');
		Session::put('UserID', $owner_id);
		//$request = \Request();
		//$import_controller = new \App\Http\Controllers\PhotoController($this->photoFunctions, $this->albumFunctions);


		$photos = Photo::select(['id', 'url'])->get();

		if (count($photos) == 0) {
			$this->line('No photos found!');
			return 0;
		}

		$this->line('Processing '.count($photos).' for classification.');
		//$bar = $this->output->createProgressBar(count($photos));
		//$bar->start();
		$this->classifyImages($script, $photos);
		//foreach ($gen_classify as $result) {
			//$id = $result[0];
			//$tags = $result[1];
			//$this->line('Classification for id='.$id.': '.$tags[0]);
		//}
		////echo $photos[0]['url'];
		////$bar->finish();
		$this->line('  ');
	}
}
