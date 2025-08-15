<?php
// v1.0
use App\Helpers\View;
use App\Helpers\Url;

$mode = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=coinedges/update' : 'index.php?route=coinedges/store';
?>
<section class="space-y-4 max-w-4xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit typ hrany' : 'Přidat typ hrany' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-4" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Název* (max 64)</span>
        <input type="text" name="name" required maxlength="64"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['name'] ?? '') ?>">
        <?php if (!empty($errors['name'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Zobrazení (max 255)</span>
        <input type="text" name="display" maxlength="255"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['display'] ?? '') ?>">
        <?php if (!empty($errors['display'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['display']) ?></div><?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Popis (max 255)</span>
      <input type="text" name="description" maxlength="255"
             class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             value="<?= htmlspecialchars($item['description'] ?? '') ?>">
      <?php if (!empty($errors['description'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
    </label>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Poznámka</span>
      <textarea name="note" rows="4"
                class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="<?= Url::build('coinedges/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
