<?php

namespace Tests\Feature;

use App\Models\Species;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ImageStore::DISK);
    }

    #[Test]
    public function une_image_est_reencodee_redimensionnee_et_renommee(): void
    {
        $file = UploadedFile::fake()->image('photo-de-vacances.jpg', 2400, 1200);

        $path = app(ImageStore::class)->store($file, 'univers');

        $this->assertTrue(ImageStore::isManagedPath($path));
        $this->assertStringNotContainsString('vacances', $path);
        Storage::disk(ImageStore::DISK)->assertExists($path);

        $size = getimagesizefromstring(Storage::disk(ImageStore::DISK)->get($path));
        $this->assertSame(1600, $size[0]);
    }

    #[Test]
    public function un_faux_fichier_image_est_refuse(): void
    {
        $file = UploadedFile::fake()->createWithContent('image.jpg', '<?php echo "pirate"; ?>');

        $this->expectException(ValidationException::class);

        app(ImageStore::class)->store($file, 'univers');
    }

    #[Test]
    public function un_chemin_d_image_qui_ne_vient_pas_du_site_est_ignore(): void
    {
        $this->assertFalse(ImageStore::isManagedPath('../.env'));
        $this->assertFalse(ImageStore::isManagedPath('univers/../../.env'));

        $species = Species::query()->firstOrFail();
        $species->update(['image_path' => '../.env']);

        $this->assertNull($species->fresh()->image_path);
    }

    #[Test]
    public function l_ancienne_image_est_supprimee_quand_elle_est_remplacee(): void
    {
        $store = app(ImageStore::class);
        $first = $store->store(UploadedFile::fake()->image('a.png', 400, 300), 'univers');
        $second = $store->store(UploadedFile::fake()->image('b.png', 400, 300), 'univers');

        $species = Species::query()->firstOrFail();
        $species->update(['image_path' => $first, 'image_credit' => 'Test']);
        $species->update(['image_path' => $second]);

        Storage::disk(ImageStore::DISK)->assertMissing($first);
        Storage::disk(ImageStore::DISK)->assertExists($second);
    }
}
