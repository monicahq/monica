<div class="col-12 section-title">
  <img src="img/people/debt/bill.svg" class="icon-section icon-money">
  <h3>
    {{ trans('people.debt_title') }}

    <span class="{{ htmldir() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="{{ route('people.debts.create', $contact) }}" class="btn">{{ trans('people.debt_add_cta') }}</a>
    </span>
  </h3>
</div>

@if (!$contact->hasDebt())

  <div class="col-12" cy-name="debt-blank-state">
    <div class="section-blank">
      <h3>{{ trans('people.debts_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="{{ route('people.debts.create', $contact) }}" cy-name="add-debt-button">{{ trans('people.debt_add_cta') }}</a>
    </div>
  </div>

@else

  <div class="col-12 debts-list">

    <ul class="table" cy-name="debts-body" cy-items="{{ $contact->debts->implode('id', ',') }}">
      @foreach($contact->debts as $debt)
      <li class="table-row" cy-name="debt-item-{{ $debt->id }}">
        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($debt->created_at) }}
        </div>
        <div class="table-cell debt-nature">
          @if ($debt->in_debt == 'yes')
            {{ trans('people.debt_you_owe', [
                'amount' => $debt->displayValue
            ]) }}
          @else
            {{ trans('people.debt_they_owe', [
                'name' => $contact->first_name,
                'amount' => $debt->displayValue
            ]) }}
          @endif
        </div>
        <div class="table-cell reason">
          @if (! is_null($debt->reason))
            {{ $debt->reason }}
          @endif
        </div>
        <div class="table-cell list-actions">
          <a href="{{ route('people.debts.edit', [$contact, $debt]) }}" cy-name="edit-debt-button-{{ $debt->id }}">
            <i class="fa fa-pencil" aria-hidden="true"></i>
          </a>
          <form method="POST" action="{{ route('people.debts.destroy', [$contact, $debt]) }}">
            @method('DELETE')
            @csrf
            <confirm message="{{ trans('people.debt_delete_confirmation') }}" cy-name="delete-debt-button-{{ $debt->id }}" :name="'delete-debt'">
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </confirm>
          </form>
        </div>

      </li>
      @endforeach
      <li class="table-row">
        <div class="table-cell"></div>
        <div class="table-cell">
          <strong>
            @if ($contact->isOwedMoney())
              {{ trans('people.debt_they_owe', [
                  'name' => $contact->first_name,
                  'amount' => App\Helpers\MoneyHelper::format($contact->totalOutstandingDebtAmount(), Auth::user()->currency)
              ]) }}
            @else
              {{ trans('people.debt_you_owe', [
                  'amount' => App\Helpers\MoneyHelper::format(-$contact->totalOutstandingDebtAmount(), Auth::user()->currency)
              ]) }}
            @endif
          </strong>
        </div>
        <div class="table-cell"></div>
        <div class="table-cell"></div>
      </li>
    </ul>

  </div>

@endif
