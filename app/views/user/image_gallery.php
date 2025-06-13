<?php
// Inisialisasi path foto profil default
$defaultPhotoPath = $GLOBALS['defaultPhotoPathGlobal'] ?? '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';

// Cek dan tampilkan daftar gambar
if (isset($images) && !empty($images)):
    foreach ($images as $image):
?>
        <div class="gallery-card" tabindex="0">
            <a href="image_detail.php?id=<?php echo $image['id']; ?>" class="gallery-link">
                <!-- Tampilkan gambar galeri -->
                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" class="gallery-image" loading="lazy" />

                <div class="gallery-overlay">
                    <!-- Tombol simpan/bookmark gambar -->
                    <button
                        class="overlay-button save-btn <?php echo $image['is_bookmarked'] ? 'saved' : ''; ?>"
                        data-image-id="<?php echo $image['id']; ?>"
                        title="Simpan gambar">
                        <i class='bx <?php echo $image['is_bookmarked'] ? 'bxs-bookmark' : 'bx-bookmark'; ?>'></i>
                    </button>
                </div>
            </a>
            <div class="card-footer">
                <!-- Tampilkan profil penulis gambar -->
                <a href="public_profile.php?username=<?php echo htmlspecialchars($image['username']); ?>" class="author-link">
                    <img src="<?php echo htmlspecialchars(!empty($image['user_photo']) ? $image['user_photo'] : $defaultPhotoPath); ?>" alt="Foto profil <?php echo htmlspecialchars($image['username']); ?>" class="author-avatar">
                    <span class="author-name"><?php echo htmlspecialchars($image['username']); ?></span>
                </a>
            </div>
        </div>
<?php
    endforeach;
endif;
?>