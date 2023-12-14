<?php

namespace App\Http\Controllers\Admin;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Watch;

class WatchController extends Controller
{
    public function index()
    {

        //recupero tutti gli orologi dal db
        $watches = Watch::all();

        return view('dashboard', [
            'watches' => $watches
        ]);
    }

    public function show($slug)
    {

        $watch = Watch::where('slug', $slug)->first();

        return view('watches.show', [
            'watch' => $watch
        ]);
    }

    public function create()
    {
        return view('watches.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();

        //per upload file fatto: file disc public, creato link php artisan storage:link
        //e aggiunto enctype
        //$data['images'] = json_encode(explode(",", $data['images']));

        $labels = [
            'Brand',
            'Model',
            'Ref. No.',
            'Case Size',
            'Case Material',
            'Bezel',
            'Bracelet Material',
            'Box',
            'Cards/Papers',
            'Condition'
        ];

        $data['labels'] = json_encode($labels);

        //aggiungi lo slug
        $data['slug'] = $this->generateSlug($data['brand'] . ' ' . $data['model']);

        //gestione delle immagini
        if ($request->has('images')) {
            //ciclo sull'array di istanze di file che mi viene passato nella request
            foreach ($request->file('images') as $imageFile) {
                $image_name = $data['model'].'-image-'.time().rand(1,1000).'.'.$imageFile->extension();
                //ora spostiamo l'immagine dentro lo storage dentro una cartella che sarà
                //sempre diversa in base alla funzione time()
                $imageFile->move(public_path('watch_folder'.time()),$image_name); //questa riga da rivedere
            }
        }

        $newWatch = new Watch();
        $newWatch->fill($data);
        $newWatch->save();

        return redirect()->route('dashboard');
    }

    public function edit($slug)
    {
        $watch = Watch::where('slug', $slug)->firstOrFail();

        return view('watches.edit', [
            'watch' => $watch
        ]);
    }


    public function update(Request $request, $slug)
    {
        //recupero dal db l'orologio che voglio modificare
        $watchToEdit = Watch::where('slug', $slug)->firstOrFail();

        $data = $request->all();

        $data['images'] = json_encode(explode(",", $data['images']));

        
        $labels = [
            'Brand',
            'Model',
            'Ref. No.',
            'Case Size',
            'Case Material',
            'Bezel',
            'Bracelet Material',
            'Box',
            'Cards/Papers',
            'Condition'
        ];
        //se è null il campo delle etichette dell'orologio da modificare allora lo inizializzo
        if (is_null($watchToEdit->labels)) {
            $watchToEdit->labels = json_encode($labels);
        } else if (json_decode($watchToEdit->labels) !== $labels) {
            $watchToEdit->labels = json_encode($labels);
        }

        if ($data['brand'] == $watchToEdit->brand && $data['model'] == $watchToEdit->model) {
            $data['slug'] = $watchToEdit->slug;
        } else { //se è cambiato il modello o la marca dell'orologio modifico lo slug
            $data['slug'] = $this->generateSlug($data['brand'] . ' ' . $data['model']);
        }

        $watchToEdit->update($data);

        return redirect()->route('watches.show', $watchToEdit->slug);
    }


    public function destroy($slug) {
        $watchToDelete = Watch::where('slug', $slug)->firstOrFail();

        $watchToDelete->delete();

        return redirect()->route('dashboard');
    }






    protected function generateSlug($title)
    {
        $counter = 0;

        do {
            $slug = Str::slug($title, '-') . ($counter > 0 ? '-' . $counter : '');

            $alreadyExists = Watch::where('slug', $slug)->first();

            $counter++;
        } while ($alreadyExists);

        return $slug;
    }
}
