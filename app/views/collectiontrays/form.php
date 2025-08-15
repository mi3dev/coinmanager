<?php
// v1.0
use App\Helpers\View;
use App\Helpers\Url;

$mode = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=collectiontrays/update' : 'index.php?route=collectiontrays/store';
?>
<section class="space-y-4 max-w-3xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit plato' : 'Přidat plato' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-4" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Název* (max 128)</span>
      <input type="text" name="name" required maxlength="128"
             class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             value="<?= htmlspecialchars($item['name'] ?? '') ?>">
      <?php if (!empty($errors['name'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
    </label>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Popis</span>
      <textarea name="description" rows="3"
                class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
      <?php if (!empty($errors['description'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
    </label>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Pořadí*</span>
      <input type="number" name="position" inputmode="numeric" step="1" min="1"
             class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             value="<?= htmlspecialchars((string)($item['position'] ?? 1)) ?>">
      <?php if (!empty($errors['position'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['position']) ?></div><?php endif; ?>
      <div class="mt-1 text-xs text-slate-400">Při vložení se položky od zadaného pořadí posunou.</div>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="<?= Url::build('collectiontrays/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
