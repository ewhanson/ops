<?php

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;

class I7014_DoiMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DOIs
        Schema::create('dois', function (Blueprint $table) {
            $table->bigInteger('doi_id')->autoIncrement();
            $table->bigInteger('context_id');
            $table->string('doi');
            $table->smallInteger('status')->default(1);

            $table->foreign('context_id')->references('server_id')->on('servers');
        });

        // Settings
        Schema::create('doi_settings', function (Blueprint $table) {
            $table->bigInteger('doi_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();

            // TODO: #doi Check on index/unique alongside foreign. Is it okay?
            $table->index(['doi_id'], 'doi_settings_doi_id');
            $table->unique(['doi_id', 'locale', 'setting_name'], 'doi_settings_pkey');
            $table->foreign('doi_id')->references('doi_id')->on('dois');
        });

        // Add doiId to publication
        Schema::table('publications', function (Blueprint $table) {
            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Add doiId to galley
        Schema::table('publication_galleys', function (Blueprint $table) {
            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        $this->migrateExistingDataUp();
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

    private function migrateExistingDataUp()
    {
        // TODO: implement data migration
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I7014_DoiMigration', '\I7014_DoiMigration');
}
