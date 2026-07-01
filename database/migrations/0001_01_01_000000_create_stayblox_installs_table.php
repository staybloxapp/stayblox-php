<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stayblox_installs', function (Blueprint $table): void {
            $table->id();
            $table->string('team_slug')->unique();
            $table->text('access_token');
            $table->text('webhook_secret');
            $table->json('granted_scopes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stayblox_installs');
    }
};
