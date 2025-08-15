<?php
// v1.0
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label,$col,$curS,$curD){
  $next = Sort::nextDir($col,$curS,$curD);
  $url  = Url::build('collection/list',['sort'=>$col,'dir'=>$next,'page'=>1]);
  $c = ($curS===$col)?($curD==='ASC'?' ▲':' ▼'):'';
  return '<a href="'.$url.'" class="hover:underline">'.$label.$c.'</a>';
}
$routeForPager = $_GET['route'] ?? 'collection/list';
$imgBase = 'public/uploads/collection/';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Moje sbírka</h1>
    <a href="<?= Url::build('collection/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">+ Přidat</a>
  </div>

  <form method="get" class="grid gap-2 sm:grid-cols-4">
    <input type="hidden" name="route" value="collection/list">
    <input class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
           type="text" name="q" placeholder="Hledat v názvu/poznámce…" value="<?= htmlspecialchars($q ?? '') ?>">
    <select name="tray" class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
      <option value="0">Všechna plata</option>
      <?php foreach ($trays as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= ((int)($tray??0)===(int)$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" type="text" name="year" inputmode="numeric" pattern="^\d{1,4}$" placeholder="Rok" value="<?= htmlspecialchars($year ?? '') ?>">
    <label class="inline-flex items-center gap-2 text-sm">
      <input type="checkbox" name="only_in" value="1" <?= !empty($only)?'checked':'' ?>> pouze ve sbírce
    </label>
    <div class="sm:col-span-4">
      <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Filtrovat</button>
    </div>
  </form>

  <?= View::flashHtml() ?>

  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2"><?= sortLink('ID','id',$sort,$dir) ?></th>
          <th class="px-3 py-2">Líc</th>
          <th class="px-3 py-2">Rub</th>
          <th class="px-3 py-2 text-left">Název / období / nominál</th>
          <th class="px-3 py-2 text-left"><?= sortLink('Rok','year',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left">Kvalita</th>
          <th class="px-3 py-2 text-left">Plato</th>
          <th class="px-3 py-2 text-left"><?= sortLink('Aktualizace','updated_at',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= (int)$r['id'] ?></td>
            <td class="px-3 py-2">
              <?php if (!empty($r['obverseImage'])): ?>
                <img src="<?= $imgBase.htmlspecialchars($r['obverseImage']) ?>" class="h-12 w-12 object-cover rounded-md border border-slate-700" alt="">
              <?php else: ?><span class="text-slate-500">–</span><?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <?php if (!empty($r['reverseImage'])): ?>
                <img src="<?= $imgBase.htmlspecialchars($r['reverseImage']) ?>" class="h-12 w-12 object-cover rounded-md border border-slate-700" alt="">
              <?php else: ?><span class="text-slate-500">–</span><?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <div class="font-medium"><?= htmlspecialchars($r['commemorativeTitle'] ?? '(bez názvu)') ?></div>
              <div class="text-xs text-slate-400"><?= htmlspecialchars($r['periodName'] ?? '') ?> · <?= htmlspecialchars($r['denomName'] ?? '') ?></div>
              <?php if (!empty($r['variantType']) && $r['variantType']!=='none'): ?>
                <div class="text-xs text-sky-300 mt-0.5">Varianta: <?= htmlspecialchars($r['variantType']) ?><?= $r['variantDescription']? ' — '.htmlspecialchars($r['variantDescription']) : '' ?></div>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['year']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['grade'] ?? '') ?><?= !empty($r['inCollection']) ? '' : ' (mimo sbírku)' ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['trayId'] ? ($trays[array_search($r['trayId'], array_column($trays,'id'))]['name'] ?? '') : '') ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['updated_at'] ?? $r['created_at'] ?? '') ?></td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800" href="<?= Url::build('collection/edit',['id'=>$r['id']]) ?>">Upravit</a>
              <form class="inline-block" method="post" action="<?= Url::build('collection/delete') ?>" onsubmit="return confirm('Opravdu smazat položku?');">
                <?= View::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="rounded-md border border-red-700 px-2 py-1 text-xs text-red-300 hover:bg-red-950/30" type="submit">Smazat</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="px-3 py-6 text-slate-400">Zatím žádné položky.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($p->pages > 1): ?>
    <div class="flex flex-wrap items-center gap-1 mt-4">
      <?php
        $urlFirst = Url::build($routeForPager, ['page'=>$p->firstPage()]);
        $urlPrev  = Url::build($routeForPager, ['page'=>$p->prevPage()]);
        $urlNext  = Url::build($routeForPager, ['page'=>$p->nextPage()]);
        $urlLast  = Url::build($routeForPager, ['page'=>$p->lastPage()]);
      ?>
      <a href="<?= $urlFirst ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>
      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg===null): ?><span class="px-2 text-slate-400">…</span><?php else:
          $active = ($pg===$p->page); $url=Url::build($routeForPager,['page'=>$pg]); ?>
          <a href="<?= $url ?>" class="px-3 py-1 rounded <?= $active?'bg-sky-500 text-slate-950':'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>"><?= $pg ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= $urlNext ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
