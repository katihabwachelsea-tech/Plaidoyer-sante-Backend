<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Exception;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $biographies = [
            'Gynécologue' => 'Gynécologue attentive au suivi de la santé féminine, de la grossesse et du bien-être de ses patientes.',
            'Ophtalmologue' => 'Ophtalmologue spécialisé dans le diagnostic et le traitement des troubles de la vue et des affections oculaires.',
            'Orthopédiste' => 'Orthopédiste spécialisé dans le diagnostic et le traitement des douleurs articulaires et des traumatismes.',
            'Neurologue' => 'Neurologue dédié au suivi des troubles neurologiques, des céphalées et des maladies du système nerveux.',
            'Médecin généraliste' => 'Médecin généraliste offrant un suivi médical global, la prévention et le traitement des maladies courantes.',
            'Oncologue' => 'Oncologue spécialisé dans le diagnostic, le suivi et la prise en charge des maladies cancéreuses.',
            'Endocrinologue' => 'Endocrinologue spécialisé dans le suivi du diabète, des hormones et des troubles métaboliques.',
            'Cardiologue' => 'Cardiologue spécialisé dans le suivi cardiovasculaire, le dépistage et la prévention des maladies du cœur.',
            'Pédiatre' => 'Pédiatre attentive au suivi médical des enfants et à la prévention des maladies courantes.',
            'Dermatologue' => 'Dermatologue qui accompagne ses patients dans le traitement des affections de la peau et des soins dermatologiques.',
        ];

        foreach ($biographies as $specialite => $biographie) {
            DB::table('medecins')
                ->whereRaw('LOWER(TRIM(specialite)) = ?', [mb_strtolower(trim($specialite))])
                ->update(['biographie' => $biographie]);
        }

        DB::table('medecins')
            ->whereNull('biographie')
            ->orWhere('biographie', '')
            ->orWhereRaw('TRIM(biographie) = ""')
            ->update([
                'biographie' => 'Médecin spécialisé dans le suivi médical personnalisé et le bien-être de ses patients.',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No irreversible change is made here because this is a data normalization migration.
        // The previous values are not reliably recoverable in a generic way.
    }
};
