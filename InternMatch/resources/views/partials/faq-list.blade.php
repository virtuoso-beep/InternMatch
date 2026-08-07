<div class="accordion-x">
  @foreach([
    ['Who can use InternMatch?','Enrolled UM Tagum College students on practicum, practicum coordinators, program chairs, the dean, system administrators, and accredited host establishment supervisors.'],
    ['How are internships recommended?','Your competency statements are compared semantically against real internship task descriptions, then reranked using historical placement outcomes and a geospatial accessibility score.'],
    ['Does the system decide my placement?','No. Recommendations are ranked suggestions. Your practicum coordinator reviews and approves every deployment.'],
    ['Can I see why a company was recommended?','Yes. Every recommendation has a "Why this match" panel that lists the matched competencies, distance, capacity, and model confidence.'],
    ['What documents do I need to submit?','Requirements follow CHED CMO No. 104 and your program: parental consent, medical certificate, insurance, endorsement letter, MOA, and training plan, among others.'],
    ['How is my personal data protected?','Access is role-based, actions are recorded in an audit trail, and data handling follows the Data Privacy Act of 2012 (RA 10173).'],
    ['Can host establishments post opportunities?','Accredited supervisors can publish opportunities and slots, subject to coordinator review and an active MOA.'],
  ] as $i => $f)
    <div class="ac-item {{ $i === 0 ? 'open' : '' }}">
      <button type="button">{{ $f[0] }}<i class="bi bi-chevron-down"></i></button>
      <div class="ac-body"><p>{{ $f[1] }}</p></div>
    </div>
  @endforeach
</div>
