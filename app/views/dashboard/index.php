<?php // v1.1 ?>
<section class="space-y-6">
  <h1 class="text-lg font-semibold">Pracovní plocha</h1>

  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
      <div class="text-xs text-slate-400">Nominály v katalogu</div>
      <div class="mt-1 text-2xl font-bold"><?= (int)$stats['catalog_items'] ?></div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
      <div class="text-xs text-slate-400">Ročníky v katalogu</div>
      <div class="mt-1 text-2xl font-bold"><?= (int)$stats['catalog_entries'] ?></div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
      <div class="text-xs text-slate-400">Období</div>
      <div class="mt-1 text-2xl font-bold"><?= (int)$stats['periods'] ?></div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
      <div class="text-xs text-slate-400">Moje mince</div>
      <div class="mt-1 text-2xl font-bold"><?= (int)$stats['my_coins'] ?></div>
    </div>
  </div>

  <div class="rounded-xl border border-slate-800 bg-slate-900">
    <div class="border-b border-slate-800 px-4 py-3 text-sm font-medium">Poslední změny v katalogu</div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-slate-400">
          <tr>
            <th class="py-2 pr-4 text-left">ID</th>
            <th class="py-2 pr-4 text-left">Nominál</th>
            <th class="py-2 pr-4 text-left">Rok</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <?php foreach ($recent as $r): ?>
            <tr class="hover:bg-slate-800/40">
              <td class="py-2 pr-4"><?= (int)$r['id'] ?></td>
              <td class="py-2 pr-4"><?= htmlspecialchars($r['itemName']) ?></td>
              <td class="py-2 pr-4"><?= (int)$r['year'] ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?>
            <tr><td colspan="3" class="py-6 text-slate-400">Žádné položky.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
