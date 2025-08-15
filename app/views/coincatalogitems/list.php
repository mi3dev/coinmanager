<?php
// v1.1
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label,$col,$curS,$curD){
  $next = Sort::nextDir($col,$curS,$curD);
  $url  = Url::build('coincatalogitems/list',['sort'=>$col,'dir'=>$next,'page'=>1]);
  $c = ($curS===$col)?($curD==='ASC'?' ▲':' ▼'):'';
  return '<a href="'.$url.'" class="hover:underline">'.$label.$c.'</a>';
}
$routeForPager = $_GET['route'] ?? 'coincatalogitems/list';
$imgBase = 'public/uploads/coins/';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Katalog – nominály</h1>
    <a href="<?= Url::build('coincatalogitems/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">+ Nová položka</a>
  </div>

  <form method="get" class="flex gap-2">
    <input type="hidden" name="route" value="coincatalogitems/list">
    <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
           type="text" name="q" placeholder="Hledat podle ID, názvu (commemorativeTitle), poznámky…"
           value="<?= htmlspecialchars($q ?? '') ?>">
    <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Hledat</button>
  </form>

  <?= View::flashHtml() ?>

  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2 text-left"><?= sortLink('ID','id',$sort,$dir) ?></th>
          <th class="px-3 py-2">Avers</th>
          <th class="px-3 py-2">Revers</th>
          <th class="px-3 py-2 text-left"><?= sortLink('Název / období / typ / nominál','unit,value,commemorativeTitle',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Design od–do','designYearFrom',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Aktualizace','updated_at',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): $id=(int)$r['id']; ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= $id ?></td>
            <td class="px-3 py-2">
              <?php if (!empty($r['obverseImage'])): ?>
                <img src="<?= $imgBase . htmlspecialchars($r['obverseImage']) ?>" alt="" class="h-12 w-12 object-cover rounded-md border border-slate-700">
              <?php else: ?>
                <span class="text-slate-500">–</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <?php if (!empty($r['reverseImage'])): ?>
                <img src="<?= $imgBase . htmlspecialchars($r['reverseImage']) ?>" alt="" class="h-12 w-12 object-cover rounded-md border border-slate-700">
              <?php else: ?>
                <span class="text-slate-500">–</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <div class="font-medium"><?= htmlspecialchars($r['denomName'] ?? '') ?>&nbsp;<?= htmlspecialchars($r['commemorativeTitle'] ?? '') ?></div>
              <div class="text-xs text-slate-400">
                <?= htmlspecialchars($r['periodName'] ?? '') ?> ·
                <?= htmlspecialchars($r['typeName'] ?? '') ?> 
                
              </div>
              <?php
                $obv = $counts[$id]['obverse'] ?? '';
                $rev = $counts[$id]['reverse'] ?? '';
              ?>
              <div class="text-xs text-slate-400 mt-0.5">Autoři: líc <?= $obv ?> · rub <?= $rev ?></div>
            </td>
            <td class="px-3 py-2">
              <?= htmlspecialchars($r['designYearFrom'] ?? '') ?>–<?= htmlspecialchars($r['designYearTo'] ?? '') ?>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['updated_at'] ?? $r['created_at'] ?? '') ?></td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('coincatalogitems/edit',['id'=>$id]) ?>">Upravit</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="px-3 py-6 text-slate-400">Žádné záznamy.</td></tr>
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
