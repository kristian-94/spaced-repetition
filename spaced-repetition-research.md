# Spaced Repetition: Research & Reference

## What Is Spaced Repetition?

Spaced repetition is a learning technique that schedules reviews of material at increasing intervals over time — showing you information *just before you're about to forget it*. This exploits two well-established cognitive phenomena:

1. **The Forgetting Curve** (Ebbinghaus, 1885): Without review, humans forget roughly half of newly learned information within days. The curve is steep at first and flattens over time.
2. **The Spacing Effect**: Each time you successfully recall something, the memory gets stronger and the interval before you need to review it again gets longer. Reviewing at the right moment is far more efficient than re-reading repeatedly.

The goal: achieve a target retention rate (e.g. 90%) with the *minimum number of reviews possible*.

---

## Core Concepts

### Active Recall
You must actively retrieve the answer from memory (not just re-read it). This is what makes flashcards more effective than passive review — the act of trying to remember something strengthens the memory trace.

### Optimal Review Timing
Review just before the point of forgetting. Too early = wasted review. Too late = you've already forgotten and must re-learn. The algorithm predicts this timing per card.

---

## Card States (Anki-style)

Cards move through these states:

| State | Description |
|-------|-------------|
| **New** | Never seen before. Waiting to be introduced. |
| **Learning** | Recently introduced; being reviewed with short intervals (minutes to a day) until stable. |
| **Review** | Graduated into long-term scheduling. Shown at increasing intervals (days, weeks, months). |
| **Lapsed** | A review card that was forgotten. Sent back to learning with a reduced interval. |

---

## Rating System

When you review a card, you rate how well you remembered it. Classic options:

| Rating | Meaning | Effect |
|--------|---------|--------|
| **Again** (0–2) | Forgot it completely | Reset: card goes back to learning, interval shrinks |
| **Hard** (3) | Remembered with serious difficulty | Interval grows slowly, ease factor decreases |
| **Good** (4) | Remembered with minor difficulty | Normal interval growth |
| **Easy** (5) | Remembered instantly, no effort | Interval grows faster, ease factor increases |

---

## The SM-2 Algorithm (Classic)

Developed by Piotr Woźniak in 1987. Powers Anki and many other apps. Simple, effective, and well-understood.

### Per-card variables:
- **Repetitions (n)**: How many times the card has been reviewed successfully in a row
- **Ease Factor (EF)**: A multiplier, starting at 2.5. Adjusts based on how hard the card is.
- **Interval (I)**: Days until next review

### Scheduling rules:

```
If quality < 3 (forgot it):
  Reset repetitions to 0
  Set interval back to 1 day

If quality >= 3 (remembered it):
  n=1: interval = 1 day
  n=2: interval = 6 days
  n>2: interval = previous_interval × ease_factor

After each review:
  EF = EF + (0.1 - (5 - quality) × (0.08 + (5 - quality) × 0.02))
  EF must not go below 1.3
```

### Example progression for an "Easy" card:
- Review 1: 1 day later
- Review 2: 6 days later
- Review 3: 15 days later (6 × 2.5)
- Review 4: 37 days later (15 × 2.5)
- Review 5: ~3 months later

### Key insight:
SM-2 is adaptive. Hard cards get reviewed more often (low EF = slow interval growth). Easy cards space out quickly. No machine learning needed — just a few variables.

---

## The FSRS Algorithm (Modern)

**Free Spaced Repetition Scheduler** — developed by Jarrett Ye, now built into Anki (v23.10+).

### Why it's better than SM-2:
- 20–30% fewer reviews needed for the same retention level
- Uses a Three-Component Memory Model instead of a simple ease factor
- Personalizes to your actual forgetting patterns over time

### Three memory components tracked per card:
| Component | Description |
|-----------|-------------|
| **Stability (S)** | Days until retrievability drops from 100% to 90% |
| **Difficulty (D)** | How inherently hard the card is (1–10) |
| **Retrievability (R)** | Current probability (0–1) you can recall it right now |

### How it works:
- After each review, FSRS updates S, D, and R using learned parameters
- Schedules the next review when R is predicted to reach your *desired retention* threshold (e.g. 90%)
- Over time, fits its parameters to your personal review history using machine learning

### Key difference from SM-2:
SM-2 uses a fixed ease multiplier. FSRS models the actual forgetting curve for each card based on observed memory stability. It's more accurate but harder to reason about manually.

### For simple apps:
FSRS is ideal if you want accuracy. SM-2 is ideal if you want simplicity and transparency. For an AI-assisted app, FSRS's desired-retention knob (`0.0–1.0`) is a great single user-facing control.

---

## Practical Scheduling Rules (Simplified)

For a simple app, you can implement a clean subset of these ideas:

1. **New cards**: Show with a 1-day interval on first success
2. **Successful review**: Multiply interval by ease factor (start at 2.5)
3. **Failed review**: Reset interval to 1 day, reduce ease factor slightly
4. **New card learning phase**: Show again in 1 minute, then 10 minutes, then graduate to day-scale intervals
5. **Daily limit**: Limit new cards per day (e.g. 20) to prevent overwhelming queues
6. **Overdue cards**: If you miss days, still review them — don't skip

---

## What Makes a Good Card

- **One fact per card** (atomic): Don't cram multiple concepts onto one card
- **Question front, answer back**: Force active retrieval
- **Cloze deletions**: Fill-in-the-blank format (e.g. "The capital of France is ____")
- **Use images**: Visual memory is stronger
- **Avoid ambiguity**: Vague questions lead to inconsistent ratings

---

## Key Numbers to Know

| Metric | Typical Value |
|--------|--------------|
| Starting ease factor | 2.5 |
| Minimum ease factor | 1.3 |
| Target retention rate | 90% |
| Initial learning steps | 1 min → 10 min → 1 day → 6 days |
| Daily new card limit (recommended) | 10–20 |

---

## Sources

- [SM-2 Algorithm Explained — DEV Community](https://dev.to/umangsinha12/how-spaced-repetition-actually-works-the-sm-2-algorithm-1ge3)
- [The Anki SM-2 Algorithm — RemNote Help](https://help.remnote.com/en/articles/6026144-the-anki-sm-2-spaced-repetition-algorithm)
- [What algorithm does Anki use? — Anki FAQs](https://faqs.ankiweb.net/what-spaced-repetition-algorithm)
- [FSRS Algorithm — RemNote Help](https://help.remnote.com/en/articles/9124137-the-fsrs-spaced-repetition-algorithm)
- [FSRS vs SM-2 Guide — MemoForge](https://memoforge.app/blog/fsrs-vs-sm2-anki-algorithm-guide-2025/)
- [Spaced Repetition — Wikipedia](https://en.wikipedia.org/wiki/Spaced_repetition)
- [The Ebbinghaus Forgetting Curve — Structural Learning](https://www.structural-learning.com/post/ebbinghaus-forgetting-curve)
