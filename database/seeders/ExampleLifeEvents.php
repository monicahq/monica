<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LifeEventTypeEnum;

/**
 * What happened to the people of the example account, from the show's chronology. The first
 * participant is the person the event is written from, and the others were there too. Nothing is
 * dated after the finale unless the twins took part, since their story only starts there.
 */
class ExampleLifeEvents
{
    private const string MONICA = 'Monica Geller';

    private const string RACHEL = 'Rachel Green';

    private const string ROSS = 'Ross Geller';

    private const string CHANDLER = 'Chandler Bing';

    private const string JOEY = 'Joey Tribbiani';

    private const string PHOEBE = 'Phoebe Buffay';

    private const string MIKE = 'Mike Hannigan';

    private const string GUNTHER = 'Gunther';

    private const string JANICE = 'Janice Litman';

    private const string RICHARD = 'Richard Burke';

    private const string EMILY = 'Emily Waltham';

    private const string CAROL = 'Carol Willick';

    private const string SUSAN = 'Susan Bunch';

    private const string BEN = 'Ben Geller';

    private const string EMMA = 'Emma Geller Green';

    private const string JACK = 'Jack Geller';

    private const string JUDY = 'Judy Geller';

    private const string HECKLES = 'Mr. Heckles';

    private const string JACK_BING = 'Jack Bing';

    private const string ERICA = 'Erica Bing';

    private const string FRANK = 'Frank Buffay Jr.';

    private const string URSULA = 'Ursula Buffay';

    private const string BARRY = 'Barry Farber';

    private const string NORA = 'Nora Bing';

    private const string HELENA = 'Helena Bing';

    private const string APARTMENT_20 = '90 Bedford Street, apartment 20, New York';

    private const string APARTMENT_19 = '90 Bedford Street, apartment 19, New York';

    private const string CENTRAL_PERK = 'Central Perk, New York';

    private const string LONG_ISLAND = 'The Geller house, Long Island';

    private const string WESTCHESTER = 'The house in Westchester';

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    public function all(): array
    {
        return [
            ...$this->friendsBefore2000(),
            ...$this->friendsFrom2000(),
            ...$this->gellerFamily(),
            ...$this->otherPeople(),
            ...$this->mrHeckles(),
            ...$this->twins(),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function friendsBefore2000(): array
    {
        $six = [self::MONICA, self::RACHEL, self::ROSS, self::CHANDLER, self::JOEY, self::PHOEBE];

        return [
            self::event(LifeEventTypeEnum::Memory, '1994-09-22', 'Rachel turns up at Central Perk in a wedding dress', [...$six, self::GUNTHER], self::CENTRAL_PERK, 'Rachel ran out of her own wedding and came looking for the one person she knew in the city.'),
            self::event(LifeEventTypeEnum::Move, '1994-09-22', 'Rachel moves in with Monica', [self::RACHEL, self::MONICA], self::APARTMENT_20, 'She cut up her father’s credit cards the same night, with everybody cheering.'),
            self::event(LifeEventTypeEnum::NewJob, '1994-09-29', 'Rachel starts waitressing at Central Perk', [self::RACHEL, self::GUNTHER], self::CENTRAL_PERK, 'Her first job ever. Gunther kept her on however many orders she got wrong.'),
            self::event(LifeEventTypeEnum::Separation, '1994-10-20', 'Chandler breaks up with Janice, the first time', [self::CHANDLER, self::JANICE, self::PHOEBE], self::CENTRAL_PERK, 'Phoebe offered to do it for him, and in the end he did it himself, badly.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1994-11-03', 'The blackout', [...$six], self::APARTMENT_20, 'The whole city went dark. Chandler spent it in a bank vestibule with a Victoria’s Secret model.'),
            self::event(LifeEventTypeEnum::Meal, '1994-11-17', 'Thanksgiving: the one where Underdog gets away', [...$six], self::APARTMENT_20, 'Everybody locked out while the dinner burnt. We ate grilled cheese in the end.'),
            self::event(LifeEventTypeEnum::Decision, '1994-12-15', 'Ross adopts Marcel', [self::ROSS, self::RACHEL], null, 'A capuchin monkey, rescued from a lab. He took to Rachel immediately.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1994-12-31', 'New Year’s Eve, no dates allowed', [...$six, self::JANICE], self::APARTMENT_20, 'The pact lasted until everybody turned up with somebody, Chandler with Janice.'),
            self::event(LifeEventTypeEnum::Game, '1995-03-02', 'The first poker night', [...$six], self::APARTMENT_20, 'The girls lost everything, then took lessons and lost again.'),
            self::event(LifeEventTypeEnum::Birth, '1995-05-11', 'Ben is born', [self::BEN, self::ROSS, self::CAROL, self::SUSAN, self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY, self::PHOEBE], 'Mount Sinai Hospital, New York', 'Ross and Susan fought over the name until the nurse asked for one.'),
            self::event(LifeEventTypeEnum::Memory, '1995-05-18', 'Chandler lets slip that Ross loves Rachel', [self::CHANDLER, self::RACHEL, self::ROSS], self::CENTRAL_PERK, 'At Rachel’s birthday party, the day Ross left for China.'),
            self::event(LifeEventTypeEnum::Trip, '1995-05-18', 'Ross flies to China for a dig', [self::ROSS], 'Beijing', 'Rachel ran to the airport and missed him by a few minutes.'),
            self::event(LifeEventTypeEnum::Concert, '1995-11-02', 'Phoebe sings Smelly Cat for the first time', [self::PHOEBE, self::GUNTHER, self::RACHEL, self::MONICA], self::CENTRAL_PERK, 'Written about a cat nobody could stand to be near. It became the song everybody asked for.'),
            self::event(LifeEventTypeEnum::Memory, '1995-11-09', 'Ross and Rachel kiss at Central Perk', [self::ROSS, self::RACHEL, self::GUNTHER], self::CENTRAL_PERK, 'She locked him out and let him back in. Gunther turned the lights out on them.'),
            self::event(LifeEventTypeEnum::Exercise, '1995-11-09', 'Monica becomes Chandler’s personal trainer', [self::MONICA, self::CHANDLER], 'A gym on Hudson Street', 'He cancelled every session he could. She made him do them anyway.'),
            self::event(LifeEventTypeEnum::Excursion, '1995-12-14', 'Driving Phoebe to find her father', [self::PHOEBE, self::CHANDLER, self::JOEY], 'Upstate New York', 'She got as far as his front door and went home with a new family instead.'),
            self::event(LifeEventTypeEnum::Marriage, '1996-01-18', 'Carol and Susan get married', [self::CAROL, self::SUSAN, self::BEN, self::ROSS, self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY, self::PHOEBE], 'The Lesbian Wedding, Manhattan', 'Carol’s parents refused to come, so Ross walked her down the aisle. Monica did the catering.'),
            self::event(LifeEventTypeEnum::NewJob, '1996-01-25', 'Joey is cast as Dr. Drake Ramoray', [self::JOEY, self::CHANDLER], 'NBC Studios, New York', 'Days of Our Lives. He started buying things he could not use, like a gold watch.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1996-02-01', 'Watching the prom video', [...$six], self::APARTMENT_20, 'Ross in a tuxedo, ready to take Rachel to the prom. She kissed him in front of everybody.'),
            self::event(LifeEventTypeEnum::Excursion, '1996-02-08', 'Ross and Rachel’s first date, at the museum', [self::ROSS, self::RACHEL], 'Museum of Prehistoric History, New York', 'A picnic under the stars in the planetarium, after closing.'),
            self::event(LifeEventTypeEnum::Move, '1996-02-15', 'Joey moves out', [self::JOEY, self::CHANDLER], 'A new apartment, New York', 'His new place had a pair of ceramic dogs. Chandler got Eddie instead.'),
            self::event(LifeEventTypeEnum::Move, '1996-03-28', 'Joey moves back in', [self::JOEY, self::CHANDLER], self::APARTMENT_19, 'Eddie was driven away to Yonkers, and Joey had his room back that afternoon.'),
            self::event(LifeEventTypeEnum::Memory, '1996-03-21', 'Dr. Drake Ramoray falls down an elevator shaft', [self::JOEY, self::CHANDLER], 'NBC Studios, New York', 'He told a magazine he wrote his own lines. The writers read it.'),
            self::event(LifeEventTypeEnum::Illness, '1996-05-09', 'Phoebe catches chicken pox', [self::PHOEBE, self::MONICA], 'Phoebe’s apartment, New York', 'The week her navy boyfriend was on leave. Both of them spent it in oatmeal baths.'),
            self::event(LifeEventTypeEnum::Visit, '1996-10-03', 'Phoebe meets her brother, Frank Jr.', [self::PHOEBE, self::FRANK], 'Frank Jr.’s house, upstate New York', 'He liked melting things. She liked him anyway.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '1996-10-03', 'The foosball table', [self::CHANDLER, self::JOEY], self::APARTMENT_19, 'It replaced the kitchen table. Monica beat both of them at it on the first night.'),
            self::event(LifeEventTypeEnum::Game, '1996-11-21', 'The Thanksgiving football game', [...$six], 'A park near Bedford Street', 'Monica and Ross tried to win the Geller Cup. Rachel caught the only pass she ever caught.'),
            self::event(LifeEventTypeEnum::Decision, '1996-12-12', 'Rachel quits Central Perk', [self::RACHEL, self::GUNTHER], self::CENTRAL_PERK, 'She decided to work in fashion at last. Gunther made her train her replacement first.'),
            self::event(LifeEventTypeEnum::Separation, '1997-01-16', 'Chandler sends Janice back to her husband', [self::CHANDLER, self::JANICE, self::JOEY], 'A mattress store, New York', 'Joey saw her kiss her ex husband, and Chandler told her to go back to him.'),
            self::event(LifeEventTypeEnum::NewJob, '1997-01-16', 'Rachel starts at Bloomingdale’s', [self::RACHEL], 'Bloomingdale’s, New York', 'Assistant buyer. Her first job with her name on the door, a small door.'),
            self::event(LifeEventTypeEnum::Argument, '1997-02-13', 'Ross and Rachel take a break', [self::ROSS, self::RACHEL], self::APARTMENT_20, 'It started over a man named Mark and ended with a word nobody has agreed on since.'),
            self::event(LifeEventTypeEnum::Separation, '1997-02-20', 'Ross and Rachel break up', [self::ROSS, self::RACHEL, self::MONICA, self::CHANDLER, self::JOEY, self::PHOEBE], self::APARTMENT_20, 'The rest of us were stuck in Monica’s room and heard every word.'),
            self::event(LifeEventTypeEnum::Holiday, '1997-05-15', 'The beach house in Montauk', [...$six], 'Montauk, New York', 'The weekend Phoebe went looking for her birth mother.'),
            self::event(LifeEventTypeEnum::Excursion, '1997-09-25', 'The jellyfish', [self::MONICA, self::CHANDLER, self::JOEY], 'Montauk, New York', 'Monica was stung, and what happened next is not discussed.'),
            self::event(LifeEventTypeEnum::SpiritualEvent, '1997-10-02', 'Phoebe finds her mother’s spirit in a cat', [self::PHOEBE, self::RACHEL, self::ROSS], 'Phoebe’s apartment, New York', 'Julio the cat walked into her guitar case. Ross could not keep quiet about it.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '1997-10-16', 'The embryo transfer', [self::PHOEBE, self::FRANK], 'A fertility clinic, New York', 'Phoebe agreed to carry her brother’s children, and talked to her uterus the whole time.'),
            self::event(LifeEventTypeEnum::Meal, '1997-11-20', 'Thanksgiving with Chandler in a box', [...$six], self::APARTMENT_20, 'Chandler kissed Joey’s girlfriend and paid for it in a crate all evening.'),
            self::event(LifeEventTypeEnum::Game, '1998-01-15', 'The trivia game for the apartment', [...$six], self::APARTMENT_20, 'Nobody knew what Chandler did for a living. The girls lost the apartment.'),
            self::event(LifeEventTypeEnum::Move, '1998-01-15', 'The apartments are swapped', [self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY], self::APARTMENT_19, 'Monica and Rachel moved across the hall, and hated every day of it.'),
            self::event(LifeEventTypeEnum::Move, '1998-02-26', 'The girls get apartment 20 back', [self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY], self::APARTMENT_20, 'Bought back for Knicks season tickets and a one minute kiss.'),
            self::event(LifeEventTypeEnum::Decision, '1998-04-02', 'Ross proposes to Emily', [self::ROSS, self::EMILY], 'New York', 'After six weeks. Everybody told him it was fast, and he did it anyway.'),
            self::event(LifeEventTypeEnum::Trip, '1998-05-06', 'Flying to London for Ross’s wedding', [self::ROSS, self::MONICA, self::CHANDLER, self::JOEY, self::JACK, self::JUDY], 'London', 'Joey bought a Union Jack hat before the plane had landed.'),
            self::event(LifeEventTypeEnum::Marriage, '1998-05-07', 'Ross marries Emily', [self::ROSS, self::EMILY, self::MONICA, self::CHANDLER, self::JOEY, self::RACHEL, self::JACK, self::JUDY, self::BEN], 'Montefiore, London', 'Ross said Rachel’s name at the altar.'),
            self::event(LifeEventTypeEnum::Memory, '1998-05-07', 'Monica and Chandler, London', [self::MONICA, self::CHANDLER], 'A hotel, London', 'The night nobody knew about, which is how it began.'),
            self::event(LifeEventTypeEnum::Trip, '1998-09-24', 'The honeymoon in Athens', [self::ROSS, self::RACHEL], 'Athens', 'Emily never boarded, and Rachel did, by mistake.'),
            self::event(LifeEventTypeEnum::Birth, '1998-10-08', 'Phoebe gives birth to the triplets', [self::PHOEBE, self::FRANK, self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY], 'A hospital, New York', 'Frank Jr. Jr., Leslie, and a girl named Chandler.'),
            self::event(LifeEventTypeEnum::Separation, '1998-11-05', 'Emily ends it with Ross', [self::ROSS, self::EMILY], 'On the telephone, London', 'She asked him never to see Rachel again. He could not promise.'),
            self::event(LifeEventTypeEnum::Meal, '1998-11-19', 'Thanksgiving: the turkey on Monica’s head', [...$six], self::APARTMENT_20, 'Everybody told their worst Thanksgiving. Chandler told him he loved her.'),
            self::event(LifeEventTypeEnum::Therapy, '1999-01-07', 'Ross sees a psychiatrist after the sandwich', [self::ROSS], 'Museum of Prehistoric History, New York', 'Somebody ate his Thanksgiving leftover sandwich. He was put on leave.'),
            self::event(LifeEventTypeEnum::Interview, '1999-02-04', 'Rachel interviews at Ralph Lauren', [self::RACHEL], 'Ralph Lauren, New York', 'She kissed the interviewer by mistake, and got the job anyway.'),
            self::event(LifeEventTypeEnum::Memory, '1999-02-11', 'Everybody finds out about Monica and Chandler', [...$six], self::APARTMENT_20, 'Ross found out last, from a window across the street.'),
            self::event(LifeEventTypeEnum::Marriage, '1999-05-20', 'Ross and Rachel get married in Las Vegas', [self::ROSS, self::RACHEL, self::MONICA, self::CHANDLER, self::JOEY, self::PHOEBE], 'A chapel, Las Vegas', 'Neither of them remembered it the next morning.'),
            self::event(LifeEventTypeEnum::NewJob, '1999-09-30', 'Ross starts lecturing at NYU', [self::ROSS], 'New York University', 'He put on a British accent for his first class and could not drop it.'),
            self::event(LifeEventTypeEnum::NewJob, '1999-10-14', 'Joey waits tables at Central Perk', [self::JOEY, self::GUNTHER], self::CENTRAL_PERK, 'He hired himself, more or less. Gunther never forgave him.'),
            self::event(LifeEventTypeEnum::Move, '1999-11-04', 'Monica and Chandler move in together', [self::MONICA, self::CHANDLER, self::RACHEL, self::PHOEBE], self::APARTMENT_20, 'Rachel moved out to Phoebe’s, and Monica cried for most of the week.'),
            self::event(LifeEventTypeEnum::Separation, '1999-11-04', 'Ross and Rachel divorce', [self::ROSS, self::RACHEL], 'A lawyer’s office, New York', 'He said it was annulled. It was not.'),
            self::event(LifeEventTypeEnum::Meal, '1999-11-25', 'Thanksgiving: the one where Ross got high', [...$six, self::JACK, self::JUDY], self::APARTMENT_20, 'Everybody told Jack and Judy a secret at once, and Rachel made an English trifle with beef in it.'),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function friendsFrom2000(): array
    {
        $six = [self::MONICA, self::RACHEL, self::ROSS, self::CHANDLER, self::JOEY, self::PHOEBE];

        return [
            self::event(LifeEventTypeEnum::MajorPurchase, '2000-01-06', 'Rachel buys the apothecary table', [self::RACHEL, self::PHOEBE], 'Pottery Barn, New York', 'Phoebe hates anything mass produced, so Rachel said it came from a flea market.'),
            self::event(LifeEventTypeEnum::OtherLifeEvent, '2000-03-09', 'Fire in Phoebe’s apartment', [self::PHOEBE, self::RACHEL], 'Phoebe’s apartment, New York', 'Rachel’s hair straightener. The firemen were very handsome.'),
            self::event(LifeEventTypeEnum::Move, '2000-03-16', 'Rachel moves in with Joey', [self::RACHEL, self::JOEY], self::APARTMENT_19, 'Phoebe went to Monica and Chandler’s, and Rachel won the other bedroom.'),
            self::event(LifeEventTypeEnum::Restaurant, '2000-05-11', 'Running into Richard at dinner', [self::MONICA, self::CHANDLER, self::RICHARD], 'A restaurant, Manhattan', 'Chandler had a ring in his pocket and pretended he never wanted to marry anyone.'),
            self::event(LifeEventTypeEnum::Decision, '2000-05-18', 'Chandler proposes to Monica', [self::MONICA, self::CHANDLER, self::RICHARD], self::APARTMENT_20, 'She walked in on a room of candles and proposed first.'),
            self::event(LifeEventTypeEnum::Achievement, '2001-02-15', 'Joey is nominated for a Soapie', [self::JOEY], 'A hotel ballroom, New York', 'He lost to a teenager. He took the statue home anyway.'),
            self::event(LifeEventTypeEnum::Trip, '2001-05-10', 'Flying to Las Vegas to find Chandler’s parent', [self::CHANDLER, self::MONICA, self::HELENA], 'Viva Las Gaygas, Las Vegas', 'Chandler asked Helena to the wedding, in the dressing room after the show.'),
            self::event(LifeEventTypeEnum::Meal, '2001-05-16', 'The rehearsal dinner', [self::MONICA, self::CHANDLER, self::NORA, self::JACK, self::JUDY, self::RACHEL, self::ROSS, self::JOEY, self::PHOEBE], 'A restaurant, Manhattan', 'Ross gave a speech with a slide show nobody had asked for.'),
            self::event(LifeEventTypeEnum::Marriage, '2001-05-17', 'Monica and Chandler get married', [self::MONICA, self::CHANDLER, self::RACHEL, self::ROSS, self::JOEY, self::PHOEBE, self::JACK, self::JUDY, self::NORA, self::HELENA], 'A ballroom, Manhattan', 'Joey officiated, ordained on the internet. Both of Chandler’s parents walked him down the aisle.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '2001-10-04', 'Rachel’s first sonogram', [self::RACHEL, self::ROSS], 'An obstetrician’s office, New York', 'She could not see the baby, then she could, and cried.'),
            self::event(LifeEventTypeEnum::Move, '2002-01-10', 'Rachel moves in with Ross', [self::RACHEL, self::ROSS], 'Ross’s apartment, New York', 'For the baby. It lasted until the baby could not sleep.'),
            self::event(LifeEventTypeEnum::Birth, '2002-05-16', 'Emma is born', [self::EMMA, self::RACHEL, self::ROSS, self::MONICA, self::CHANDLER, self::JOEY, self::PHOEBE, self::JACK, self::JUDY, self::JANICE], 'Mount Sinai Hospital, New York', 'Twenty hours of labour, most of them in the room next to Janice.'),
            self::event(LifeEventTypeEnum::Meeting, '2002-10-03', 'Chandler agrees to move to Tulsa', [self::CHANDLER], 'An office, New York', 'He fell asleep in a meeting and said yes to the wrong question.'),
            self::event(LifeEventTypeEnum::Memory, '2002-10-10', 'Phoebe meets Mike on a blind date', [self::PHOEBE, self::MIKE, self::JOEY], 'A restaurant, New York', 'Joey had no one to set her up with, so he picked a name at random.'),
            self::event(LifeEventTypeEnum::Decision, '2003-01-09', 'Chandler quits his job for advertising', [self::CHANDLER, self::MONICA], 'Tulsa, Oklahoma', 'Starting again as an intern, at thirty four, with Monica behind him.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '2003-05-01', 'The fertility test', [self::MONICA, self::CHANDLER], 'A fertility clinic, New York', 'The doctor said neither of us could carry it off. We sat in the car a long time.'),
            self::event(LifeEventTypeEnum::Trip, '2003-05-15', 'Barbados', [...$six, self::MIKE], 'Barbados', 'Ross gave the keynote at a palaeontology conference. It rained most of the time.'),
            self::event(LifeEventTypeEnum::Game, '2003-05-15', 'Ping pong: Monica against Mike', [self::MONICA, self::MIKE, self::PHOEBE, self::CHANDLER], 'The hotel, Barbados', 'Nobody has ever seen Monica play to lose, and she did not start then.'),
            self::event(LifeEventTypeEnum::Memory, '2003-05-15', 'Joey and Rachel kiss', [self::JOEY, self::RACHEL], 'The hotel, Barbados', 'In the corridor, after the conference dinner.'),
            self::event(LifeEventTypeEnum::Separation, '2003-10-09', 'Joey and Rachel decide to stay friends', [self::JOEY, self::RACHEL], self::APARTMENT_19, 'Every time he touched her she slapped his hand away.'),
            self::event(LifeEventTypeEnum::Decision, '2003-10-16', 'Monica and Chandler decide to adopt', [self::MONICA, self::CHANDLER], self::APARTMENT_20, 'After the fertility test, after a lot of reading.'),
            self::event(LifeEventTypeEnum::Interview, '2003-11-06', 'The adoption home study', [self::MONICA, self::CHANDLER, self::JOEY], self::APARTMENT_20, 'Joey’s letter of recommendation said we were a “mature, stable couple”, and he chose the words himself.'),
            self::event(LifeEventTypeEnum::Decision, '2003-12-11', 'Mike proposes to Phoebe', [self::PHOEBE, self::MIKE], 'A restaurant, New York', 'The ring was in the champagne glass. He had to fish it out.'),
            self::event(LifeEventTypeEnum::Meeting, '2004-01-08', 'Meeting Erica, the birth mother', [self::MONICA, self::CHANDLER], 'An adoption agency, Ohio', 'She had got us confused with another couple, a reverend and a doctor.'),
            self::event(LifeEventTypeEnum::Marriage, '2004-02-12', 'Phoebe marries Mike, in the snow', [self::PHOEBE, self::MIKE, self::MONICA, self::RACHEL, self::ROSS, self::CHANDLER, self::JOEY], 'The street outside Central Perk', 'A blizzard shut the city. Chandler walked her down the aisle, and Joey stood in for him.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '2004-04-29', 'Monica and Chandler buy the house in Westchester', [self::MONICA, self::CHANDLER, self::JANICE], self::WESTCHESTER, 'With a yard, and Janice looking at the house next door.'),
            self::event(LifeEventTypeEnum::NewJob, '2004-04-29', 'Rachel accepts a job in Paris', [self::RACHEL], 'Louis Vuitton, Paris', 'Her dream job, in a city where nobody she loved lived.'),
            self::event(LifeEventTypeEnum::Memory, '2004-05-06', 'Gunther tells Rachel he loves her', [self::GUNTHER, self::RACHEL], self::CENTRAL_PERK, 'On her last day. She kissed his cheek and told him she would think of him.'),
            self::event(LifeEventTypeEnum::Decision, '2004-05-06', 'Rachel gets off the plane', [self::RACHEL, self::ROSS], 'Ross’s apartment, New York', 'She came back to his door, and stayed.'),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function gellerFamily(): array
    {
        $bensParents = [self::BEN, self::ROSS, self::CAROL, self::SUSAN];

        return [
            self::event(LifeEventTypeEnum::Marriage, '1961-06-10', 'Jack and Judy get married', [self::JACK, self::JUDY], 'Long Island', 'Judy has told the story of the dress more often than anybody asked.'),
            self::event(LifeEventTypeEnum::Birth, '1967-10-18', 'Ross is born', [self::ROSS, self::JACK, self::JUDY], 'Long Island', 'Their first, and, as Judy has never stopped implying, their favourite.'),
            self::event(LifeEventTypeEnum::Birth, '1969-04-22', 'Monica is born', [self::MONICA, self::JACK, self::JUDY], 'Long Island', 'Jack called her his little Harmonica from the first day.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '1975-06-01', 'Jack buys the Porsche', [self::JACK, self::JUDY], self::LONG_ISLAND, 'The car he has talked about ever since, parked where the children were not allowed near it.'),
            self::event(LifeEventTypeEnum::Memory, '1987-05-29', 'Prom night', [self::MONICA, self::RACHEL, self::ROSS, self::JACK, self::JUDY], self::LONG_ISLAND, 'Rachel’s date was late, and Ross put on Jack’s tuxedo to take her.'),
            self::event(LifeEventTypeEnum::SpiritualEvent, '1994-11-10', 'Nana’s funeral', [self::MONICA, self::ROSS, self::JACK, self::JUDY, self::RACHEL, self::CHANDLER, self::JOEY, self::PHOEBE], 'A funeral home, Long Island', 'She died twice, the first time only for a few minutes.'),
            self::event(LifeEventTypeEnum::SchoolClass, '1995-02-16', 'Lamaze class', [self::ROSS, self::CAROL, self::SUSAN], 'A community centre, New York', 'Ross and Susan both wanted to be the one holding Carol’s hand.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '1994-09-29', 'The sonogram', [self::ROSS, self::CAROL, self::SUSAN], 'An obstetrician’s office, New York', 'Ross found out he was going to be a father from a blurry screen.'),
            self::event(LifeEventTypeEnum::Separation, '1994-09-21', 'Carol moves out', [self::ROSS, self::CAROL, self::SUSAN], 'Ross’s apartment, New York', 'She took the furniture and left him the couch. She was moving in with Susan.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1996-05-11', 'Ben’s first birthday', [...$bensParents, self::MONICA, self::JACK, self::JUDY], 'Carol and Susan’s apartment, New York', 'He ate the cake with both hands and nobody stopped him.'),
            self::event(LifeEventTypeEnum::Meal, '1996-04-04', 'Jack and Judy’s thirty fifth anniversary', [self::JACK, self::JUDY, self::MONICA, self::ROSS, self::RICHARD], self::LONG_ISLAND, 'Ross’s toast ran long and wandered on to Monica and Richard.'),
            self::event(LifeEventTypeEnum::Memory, '1997-03-13', 'Ben says his first word', [self::BEN, self::ROSS, self::CAROL, self::SUSAN], 'Carol and Susan’s apartment, New York', 'Ross missed it and insisted he had not, for months.'),
            self::event(LifeEventTypeEnum::Reading, '1998-02-12', 'Ross reads Ben his dinosaur book', [self::BEN, self::ROSS], 'Ross’s apartment, New York', 'Every weekend, the same book. Ben corrected him on the Latin names.'),
            self::event(LifeEventTypeEnum::Excursion, '1999-04-15', 'Ben’s day at the museum', [self::BEN, self::ROSS, self::CAROL], 'Museum of Prehistoric History, New York', 'Ross gave his son the tour he gives the school groups, twice.'),
            self::event(LifeEventTypeEnum::SchoolClass, '2000-09-07', 'Ben’s first day of school', [...$bensParents], 'A public school, New York', 'All three of his parents walked him in.'),
            self::event(LifeEventTypeEnum::HouseholdTask, '2000-10-26', 'Monica’s childhood boxes are flooded', [self::MONICA, self::JACK, self::JUDY], self::LONG_ISLAND, 'Jack kept his tax records dry and let Monica’s boxes soak up the rain.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '2000-10-26', 'Jack gives Monica the Porsche', [self::JACK, self::MONICA], self::LONG_ISLAND, 'To make up for the boxes. She drove it straight to Ross’s to show him.'),
            self::event(LifeEventTypeEnum::Holiday, '2000-12-14', 'The holiday armadillo', [self::ROSS, self::BEN, self::CHANDLER, self::JOEY, self::MONICA, self::PHOEBE, self::RACHEL], 'Ross’s apartment, New York', 'No Santa suit was left in the city, so Ross came as an armadillo to teach Ben about Hanukkah.'),
            self::event(LifeEventTypeEnum::Memory, '2001-03-08', 'Chandler teaches Ben a practical joke', [self::CHANDLER, self::BEN, self::ROSS], 'Ross’s apartment, New York', 'Ross was horrified until Ben pulled it on Carol.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2001-05-11', 'Ben’s sixth birthday', [...$bensParents, self::MONICA, self::CHANDLER], 'Carol and Susan’s apartment, New York', 'Monica made the cake in the shape of a stegosaurus, at Ross’s request.'),
            self::event(LifeEventTypeEnum::Visit, '2002-05-18', 'Ben meets his sister', [self::BEN, self::EMMA, self::ROSS, self::RACHEL, self::CAROL, self::SUSAN], 'Mount Sinai Hospital, New York', 'He asked whether she would be able to play soon. Not for a while.'),
            self::event(LifeEventTypeEnum::Visit, '2002-05-19', 'Jack and Judy bring Emma a present', [self::JACK, self::JUDY, self::EMMA, self::RACHEL, self::ROSS], 'Ross’s apartment, New York', 'A giant stuffed dog, bigger than the cot.'),
            self::event(LifeEventTypeEnum::Move, '2002-09-26', 'Rachel and Emma move in with Joey', [self::RACHEL, self::EMMA, self::JOEY], self::APARTMENT_19, 'Joey childproofed the apartment by hiding his sandwiches.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '2002-10-17', 'Emma’s check up', [self::EMMA, self::RACHEL, self::ROSS], 'Dr. Weiner, pediatrician, New York', 'Rachel took her to her own childhood pediatrician. Ross wanted a man who was not ninety.'),
            self::event(LifeEventTypeEnum::Illness, '2002-11-14', 'Emma’s first ear infection', [self::EMMA, self::RACHEL, self::ROSS], 'Ross’s apartment, New York', 'Three nights without sleep, for all three of them.'),
            self::event(LifeEventTypeEnum::Memory, '2002-12-12', 'Emma laughs for the first time', [self::EMMA, self::ROSS, self::RACHEL], 'Ross’s apartment, New York', 'At Ross rapping Baby Got Back. It is still the only thing that works.'),
            self::event(LifeEventTypeEnum::Visit, '2003-02-13', 'Monica and Chandler babysit Emma', [self::MONICA, self::CHANDLER, self::EMMA], self::APARTMENT_20, 'They practised everything the books said, on a baby who wanted her mother.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2003-05-16', 'Emma’s first birthday', [self::EMMA, self::RACHEL, self::ROSS, self::MONICA, self::CHANDLER, self::JOEY, self::PHOEBE, self::JACK, self::JUDY, self::BEN], 'Ross’s apartment, New York', 'The cake came shaped like something else entirely. Joey ate it anyway.'),
            self::event(LifeEventTypeEnum::Holiday, '2003-12-25', 'Christmas on Long Island', [self::JACK, self::JUDY, self::ROSS, self::MONICA, self::CHANDLER, self::EMMA, self::BEN, self::RACHEL], self::LONG_ISLAND, 'Judy set the table for twelve and somebody still sat on a piano stool.'),
            self::event(LifeEventTypeEnum::Visit, '2004-01-22', 'Emma spends a day with her grandparents', [self::EMMA, self::JACK, self::JUDY], self::LONG_ISLAND, 'Jack taught her to clap. Judy said it had been her idea.'),
            self::event(LifeEventTypeEnum::Exercise, '2004-03-11', 'Emma takes her first steps', [self::EMMA, self::RACHEL, self::ROSS], 'Ross’s apartment, New York', 'Three steps, towards the stuffed dog, and a fall.'),
            self::event(LifeEventTypeEnum::Reflection, '2004-03-25', 'Jack writes about his grandchildren', [self::JACK, self::JUDY], self::LONG_ISLAND, 'A letter for each of them, to be opened when they are older.'),
            self::event(LifeEventTypeEnum::Memory, '1992-03-07', 'Carol meets Susan at the gym', [self::CAROL, self::SUSAN], 'A gym, New York', 'They were on neighbouring treadmills for three months before either said a word.'),
            self::event(LifeEventTypeEnum::Decision, '1993-11-18', 'Carol tells Ross she is gay', [self::CAROL, self::ROSS], 'Ross’s apartment, New York', 'He had known for longer than he admitted, and he still took it badly.'),
            self::event(LifeEventTypeEnum::Argument, '1995-01-19', 'Susan and Ross fight over the delivery room', [self::SUSAN, self::ROSS, self::CAROL], 'Carol and Susan’s apartment, New York', 'Both wanted to be there, and both were in the end.'),
            self::event(LifeEventTypeEnum::HomeImprovement, '1995-03-23', 'Carol and Susan paint the nursery', [self::CAROL, self::SUSAN], 'Carol and Susan’s apartment, New York', 'Yellow, because nobody wanted to guess.'),
            self::event(LifeEventTypeEnum::Holiday, '1997-12-18', 'Ben’s first holiday with his mothers', [self::BEN, self::CAROL, self::SUSAN], 'Cape Cod', 'A rented cottage, and a lot of sand in the car.'),
            self::event(LifeEventTypeEnum::Hobby, '2002-09-14', 'Susan coaches Ben’s soccer team', [self::SUSAN, self::BEN, self::CAROL], 'A park, New York', 'The team lost every game, and Susan was proud of every one.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2003-05-11', 'Ben’s eighth birthday', [self::BEN, self::ROSS, self::CAROL, self::SUSAN], 'Carol and Susan’s apartment, New York', 'A dinosaur cake, again, and he asked for it.'),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function otherPeople(): array
    {
        return [
            self::event(LifeEventTypeEnum::Marriage, '1965-06-19', 'Nora and Helena get married', [self::NORA, self::HELENA], 'A chapel, New York', 'A glamorous wedding, by all accounts, and the start of a long unravelling.'),
            self::event(LifeEventTypeEnum::Birth, '1968-04-08', 'Chandler is born', [self::CHANDLER, self::NORA, self::HELENA], 'A hospital, New York', 'Their only child.'),
            self::event(LifeEventTypeEnum::Holiday, '1974-07-18', 'A summer in Maine', [self::NORA, self::HELENA, self::CHANDLER], 'Kennebunkport, Maine', 'The last holiday the three of them took together.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1976-04-08', 'Chandler’s eighth birthday', [self::CHANDLER, self::NORA, self::HELENA], 'The Bing house', 'A magician, and both parents in the same room, happily, for once.'),
            self::event(LifeEventTypeEnum::Argument, '1978-09-14', 'Nora and Helena fight over the houseboy', [self::NORA, self::HELENA], 'The Bing house', 'Chandler heard it from the stairs.'),
            self::event(LifeEventTypeEnum::Separation, '1978-11-23', 'Nora and Helena announce their divorce, at Thanksgiving', [self::NORA, self::HELENA, self::CHANDLER], 'The Bing house', 'Over the turkey. Chandler has not eaten it since.'),
            self::event(LifeEventTypeEnum::NewJob, '1979-03-01', 'Helena opens Viva Las Gaygas', [self::HELENA], 'Viva Las Gaygas, Las Vegas', 'Top billing as Helena Handbasket, every night for twenty years.'),
            self::event(LifeEventTypeEnum::Achievement, '1982-09-15', 'Nora’s first bestseller', [self::NORA], 'New York', 'An erotic novel her son refuses to read.'),
            self::event(LifeEventTypeEnum::Visit, '1995-01-05', 'Nora visits New York', [self::NORA, self::CHANDLER, self::ROSS, self::MONICA], 'A restaurant, New York', 'She kissed Ross in the restaurant, and Chandler saw.'),
            self::event(LifeEventTypeEnum::Reading, '1995-01-12', 'Chandler reads his mother’s novel', [self::CHANDLER, self::NORA], self::APARTMENT_19, 'He got to page forty and put it in the freezer.'),
            self::event(LifeEventTypeEnum::Call, '1996-11-28', 'Nora calls Chandler on Thanksgiving', [self::NORA, self::CHANDLER], null, 'From a book tour. He let it ring twice before answering.'),
            self::event(LifeEventTypeEnum::Achievement, '1997-06-05', 'Nora on a late night talk show', [self::NORA], 'NBC Studios, Burbank', 'She told the host a story about her son, who was watching.'),
            self::event(LifeEventTypeEnum::Message, '2000-05-25', 'Nora congratulates Chandler on the engagement', [self::NORA, self::CHANDLER, self::MONICA], null, 'A card with a long letter in it about love, which Chandler read once.'),
            self::event(LifeEventTypeEnum::Reflection, '2001-05-10', 'Chandler and Helena make peace', [self::HELENA, self::CHANDLER], 'Viva Las Gaygas, Las Vegas', 'Twenty years of not speaking, settled in one conversation.'),
            self::event(LifeEventTypeEnum::Concert, '2001-05-12', 'Helena’s show, for the family', [self::HELENA, self::CHANDLER, self::MONICA], 'Viva Las Gaygas, Las Vegas', 'Monica cheered louder than anyone in the room.'),
            self::event(LifeEventTypeEnum::Hobby, '2002-03-14', 'Helena rehearses a new number', [self::HELENA], 'Viva Las Gaygas, Las Vegas', 'Sequins, a staircase, and a song she wrote herself.'),
            self::event(LifeEventTypeEnum::Call, '2003-10-20', 'Helena calls about the adoption', [self::HELENA, self::CHANDLER], null, 'She cried on the telephone, which Chandler did not expect.'),
            self::event(LifeEventTypeEnum::Achievement, '2003-06-19', 'Nora’s twentieth novel', [self::NORA], 'New York', 'Dedicated to her son, which surprised him.'),
            self::event(LifeEventTypeEnum::Hobby, '2003-09-11', 'Helena teaches a dance class', [self::HELENA], 'Las Vegas', 'Beginners, on Tuesday nights, for fun.'),
            self::event(LifeEventTypeEnum::Concert, '1989-03-01', 'Helena’s tenth anniversary show', [self::HELENA], 'Viva Las Gaygas, Las Vegas', 'A standing ovation, and roses thrown from the balcony.'),
            self::event(LifeEventTypeEnum::Memory, '2001-05-17', 'Nora and Helena dance at the wedding', [self::NORA, self::HELENA], 'A ballroom, Manhattan', 'One dance, and they did not step on each other.'),

            self::event(LifeEventTypeEnum::Visit, '1988-08-10', 'Chandler meets Janice at a party', [self::JANICE, self::CHANDLER], 'New York', 'He heard her laugh from across the room.'),
            self::event(LifeEventTypeEnum::Marriage, '1990-06-02', 'Janice marries Gary Litman', [self::JANICE], 'Long Island', 'The mattress king. It lasted longer than anybody expected.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1995-02-09', 'Chandler and Janice, again', [self::JANICE, self::CHANDLER], 'New York', 'Valentine’s Day. He had sworn it was over.'),
            self::event(LifeEventTypeEnum::Separation, '1996-06-20', 'Janice separates from Gary', [self::JANICE], 'Long Island', 'The divorce took two years and a lot of mattresses.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1996-11-07', 'Chandler and Janice, once more', [self::JANICE, self::CHANDLER, self::JOEY], self::APARTMENT_19, 'Joey could not stand her, and moved his chair to prove it.'),
            self::event(LifeEventTypeEnum::Game, '1996-12-05', 'Janice at poker night', [self::JANICE, self::CHANDLER, self::JOEY, self::ROSS], self::APARTMENT_19, 'She won every hand and laughed at every one of them.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1999-01-14', 'Ross dates Janice', [self::JANICE, self::ROSS], 'New York', 'He was so depressed that even Janice could not stand it.'),
            self::event(LifeEventTypeEnum::Birth, '1998-03-19', 'Janice has a baby', [self::JANICE], 'A hospital, Long Island', 'A boy, Gary Litman junior, named after the father.'),
            self::event(LifeEventTypeEnum::Visit, '2001-04-12', 'Janice turns up at Central Perk', [self::JANICE, self::CHANDLER, self::MONICA, self::GUNTHER], self::CENTRAL_PERK, 'She had heard about the engagement and wanted to be there. Nobody had invited her.'),
            self::event(LifeEventTypeEnum::Call, '2002-06-06', 'Janice calls to compare babies', [self::JANICE, self::RACHEL], null, 'Rachel pretended the line was breaking up, for ten minutes.'),
            self::event(LifeEventTypeEnum::Concert, '2003-03-20', 'Janice sings at a club', [self::JANICE], 'A club, New York', 'Karaoke. The owner asked her to come back on a quieter night.'),
            self::event(LifeEventTypeEnum::Holiday, '2003-08-14', 'Janice in the Hamptons', [self::JANICE], 'The Hamptons', 'She sent Chandler a postcard of herself, laughing.'),
            self::event(LifeEventTypeEnum::Visit, '2004-04-22', 'Janice views the house next door', [self::JANICE, self::MONICA, self::CHANDLER], 'Westchester', 'Chandler pretended to love Westchester to put her off buying it.'),
            self::event(LifeEventTypeEnum::Reflection, '2004-05-01', 'Janice wishes Chandler well', [self::JANICE, self::CHANDLER], 'On the telephone', 'Oh. My. God. A baby. She was glad, and she said so.'),
            self::event(LifeEventTypeEnum::Visit, '1999-05-13', 'Janice runs into Chandler at the mall', [self::JANICE, self::CHANDLER], 'A mall, New Jersey', 'He hid behind a pillar, and she found him anyway.'),

            self::event(LifeEventTypeEnum::Graduation, '1962-06-15', 'Richard graduates from medical school', [self::RICHARD], 'Columbia University, New York', 'Top of his year in ophthalmology.'),
            self::event(LifeEventTypeEnum::Hobby, '1985-07-06', 'Fishing with Jack', [self::RICHARD, self::JACK], 'Long Island Sound', 'Every summer, for years. Neither of them caught much.'),
            self::event(LifeEventTypeEnum::Separation, '1993-04-20', 'Richard’s divorce', [self::RICHARD], 'New York', 'After thirty years of marriage, amicably, which he never quite believed.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '1996-01-11', 'Richard examines Monica’s eyes', [self::RICHARD, self::MONICA], 'Burke ophthalmology, New York', 'She blinked when she should have looked, and he laughed at her.'),
            self::event(LifeEventTypeEnum::Visit, '1996-02-08', 'Monica caters Richard’s party', [self::RICHARD, self::MONICA, self::JACK, self::JUDY], 'Richard’s apartment, New York', 'They kissed in the kitchen, with her parents in the next room.'),
            self::event(LifeEventTypeEnum::Holiday, '1996-03-14', 'A weekend at Richard’s cabin', [self::RICHARD, self::MONICA], 'Richard’s cabin, upstate New York', 'He cooked for her, badly, and she let him.'),
            self::event(LifeEventTypeEnum::Decision, '1996-04-18', 'Richard shaves his moustache', [self::RICHARD, self::MONICA], 'Richard’s apartment, New York', 'He looked twenty years younger. She asked him to grow it back.'),
            self::event(LifeEventTypeEnum::Separation, '1996-05-16', 'Monica and Richard break up', [self::MONICA, self::RICHARD], 'Barry and Mindy’s wedding, Long Island', 'He did not want more children, and she did.'),
            self::event(LifeEventTypeEnum::Cinema, '1997-01-30', 'Monica and Richard at the video store', [self::MONICA, self::RICHARD], 'A video store, New York', 'They reached for the same film and decided to stay friends. It lasted a night.'),
            self::event(LifeEventTypeEnum::Hobby, '1998-06-13', 'Richard takes up golf', [self::RICHARD, self::JACK], 'A golf course, Long Island', 'Jack beat him every week and told the whole club.'),
            self::event(LifeEventTypeEnum::Holiday, '1999-02-18', 'Richard in Italy', [self::RICHARD], 'Florence', 'Three weeks, alone, looking at paintings.'),
            self::event(LifeEventTypeEnum::Meal, '2000-02-10', 'Dinner with Jack and Judy', [self::RICHARD, self::JACK, self::JUDY], self::LONG_ISLAND, 'They asked about his love life, and he changed the subject.'),
            self::event(LifeEventTypeEnum::Reflection, '2000-05-25', 'Richard lets Monica go', [self::RICHARD], 'Richard’s apartment, New York', 'He had asked her to marry him too late, and he knew it.'),
            self::event(LifeEventTypeEnum::Call, '2001-05-24', 'Richard sends his congratulations', [self::RICHARD, self::MONICA], null, 'He called the week after the wedding, and meant it.'),
            self::event(LifeEventTypeEnum::Achievement, '2002-09-19', 'Richard retires from practice', [self::RICHARD], 'Burke ophthalmology, New York', 'Forty years of eyes. His patients threw him a party.'),
            self::event(LifeEventTypeEnum::Holiday, '2003-07-10', 'Richard sails the Greek islands', [self::RICHARD], 'The Cyclades', 'He learnt to sail at sixty two, as he had always said he would.'),

            self::event(LifeEventTypeEnum::Visit, '1998-02-19', 'Ross meets Emily at the opera', [self::EMILY, self::ROSS, self::RACHEL], 'The Metropolitan Opera, New York', 'Rachel had tickets and no time, and sent Ross in her place.'),
            self::event(LifeEventTypeEnum::Holiday, '1998-02-26', 'Ross and Emily in Vermont', [self::EMILY, self::ROSS], 'An inn, Vermont', 'Two weeks together at most, and they spent one of the weekends here.'),
            self::event(LifeEventTypeEnum::Trip, '1998-03-05', 'Emily flies back to London', [self::EMILY, self::ROSS], 'JFK airport, New York', 'He gave her a sweater to keep. She kept it.'),
            self::event(LifeEventTypeEnum::Call, '1998-03-12', 'Emily and Ross talk every night', [self::EMILY, self::ROSS], null, 'Transatlantic calls, at eleven dollars a minute.'),
            self::event(LifeEventTypeEnum::Meal, '1998-05-05', 'Dinner with the Walthams', [self::EMILY, self::ROSS, self::JACK, self::JUDY], 'The Waltham house, London', 'Emily’s father and Jack argued about who was paying for the wedding.'),
            self::event(LifeEventTypeEnum::Excursion, '1998-05-06', 'The wedding venue is being demolished', [self::EMILY, self::ROSS, self::JOEY, self::CHANDLER], 'Montefiore, London', 'Emily cried, and Joey had an idea.'),
            self::event(LifeEventTypeEnum::Meal, '1998-05-06', 'Ross and Emily’s rehearsal dinner', [self::EMILY, self::ROSS, self::MONICA, self::CHANDLER, self::JOEY, self::JACK, self::JUDY], 'A restaurant, London', 'Everybody was happy for one evening.'),
            self::event(LifeEventTypeEnum::Argument, '1998-10-15', 'Emily asks Ross never to see Rachel again', [self::EMILY, self::ROSS], 'On the telephone, London', 'He said yes, and then could not keep it.'),
            self::event(LifeEventTypeEnum::Message, '1998-10-22', 'Emily writes to Ross', [self::EMILY, self::ROSS], null, 'A long letter, in her careful handwriting, about trust.'),
            self::event(LifeEventTypeEnum::Decision, '1999-03-04', 'Emily decides to stay in London', [self::EMILY], 'London', 'Her life was there, and she made peace with that.'),
            self::event(LifeEventTypeEnum::NewJob, '1999-06-10', 'Emily takes a new post', [self::EMILY], 'London', 'Chief buyer at a department store, finally.'),
            self::event(LifeEventTypeEnum::Holiday, '1999-08-19', 'Emily in Scotland', [self::EMILY], 'Edinburgh', 'A walking holiday, alone, and good for her.'),
            self::event(LifeEventTypeEnum::Hike, '2000-04-15', 'Emily climbs Ben Nevis', [self::EMILY], 'Ben Nevis, Scotland', 'In the rain. She would not recommend it.'),
            self::event(LifeEventTypeEnum::Marriage, '2000-09-23', 'Emily remarries', [self::EMILY], 'A church, London', 'A quiet wedding, and nobody said the wrong name.'),
            self::event(LifeEventTypeEnum::Move, '2001-02-01', 'Emily moves to the country', [self::EMILY], 'Surrey', 'A cottage with a garden, an hour from London.'),
            self::event(LifeEventTypeEnum::Birth, '2002-07-12', 'Emily has a daughter', [self::EMILY], 'A hospital, London', 'She sent Ross a card, and he kept it.'),
            self::event(LifeEventTypeEnum::Hobby, '2003-04-10', 'Emily takes up gardening', [self::EMILY], 'Surrey', 'Roses, mostly, and a vegetable patch that failed.'),

            self::event(LifeEventTypeEnum::Visit, '1984-07-20', 'Rachel meets Barry at summer camp', [self::BARRY, self::RACHEL], 'A summer camp, Long Island', 'He had braces. She had a crush on somebody else.'),
            self::event(LifeEventTypeEnum::Graduation, '1990-05-25', 'Barry graduates from dental school', [self::BARRY], 'Columbia University, New York', 'Orthodontics. His mother came in a fur coat in May.'),
            self::event(LifeEventTypeEnum::NewJob, '1991-09-03', 'Barry opens his practice', [self::BARRY], 'Farber orthodontics, Long Island', 'A dental chair, a receptionist, and his name on the door.'),
            self::event(LifeEventTypeEnum::Decision, '1993-06-12', 'Barry proposes to Rachel', [self::BARRY, self::RACHEL], 'A restaurant, Long Island', 'A large ring, and a larger family watching.'),
            self::event(LifeEventTypeEnum::Separation, '1994-09-22', 'Rachel leaves Barry at the altar', [self::RACHEL, self::BARRY], 'A synagogue, Long Island', 'She looked at a gravy boat and realised she did not love him.'),
            self::event(LifeEventTypeEnum::Holiday, '1994-10-06', 'Barry goes to Aruba alone', [self::BARRY], 'Aruba', 'Their honeymoon. He came back with a tan and a new outlook.'),
            self::event(LifeEventTypeEnum::Visit, '1995-04-06', 'Rachel visits Barry’s practice', [self::RACHEL, self::BARRY], 'Farber orthodontics, Long Island', 'He was engaged to Mindy by then. It did not stop either of them.'),
            self::event(LifeEventTypeEnum::Marriage, '1996-05-16', 'Barry marries Mindy', [self::BARRY, self::RACHEL, self::MONICA, self::RICHARD], 'A banquet hall, Long Island', 'Rachel was the maid of honour and sang with the band.'),
            self::event(LifeEventTypeEnum::Holiday, '1996-05-23', 'Barry and Mindy honeymoon in Aruba', [self::BARRY], 'Aruba', 'The same hotel. Nobody told Mindy.'),
            self::event(LifeEventTypeEnum::Training, '1997-03-20', 'Barry learns invisible braces', [self::BARRY], 'A dental conference, Chicago', 'He came back talking of nothing else.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '1998-04-09', 'Barry buys a boat', [self::BARRY], 'A marina, Long Island', 'Named after himself. Mindy was not consulted.'),
            self::event(LifeEventTypeEnum::Birth, '1999-01-21', 'Barry and Mindy have a son', [self::BARRY], 'A hospital, Long Island', 'Perfect teeth, Barry says, though he has none yet.'),
            self::event(LifeEventTypeEnum::Separation, '2000-06-15', 'Barry and Mindy separate', [self::BARRY], 'Long Island', 'Mindy found out about the dental hygienist.'),
            self::event(LifeEventTypeEnum::Therapy, '2000-09-14', 'Barry starts therapy', [self::BARRY], 'A therapist’s office, Long Island', 'Every Thursday. He talks about Rachel more than about Mindy.'),
            self::event(LifeEventTypeEnum::Move, '2001-01-18', 'Barry moves back in with his mother', [self::BARRY], 'His mother’s house, Long Island', 'Temporarily, he tells everyone.'),
            self::event(LifeEventTypeEnum::Exercise, '2001-08-09', 'Barry runs a marathon', [self::BARRY], 'Long Island', 'He finished, in five hours, and told his patients for a year.'),
            self::event(LifeEventTypeEnum::Hobby, '2002-05-02', 'Barry joins a golf club', [self::BARRY], 'A country club, Long Island', 'Mostly for the bar.'),
            self::event(LifeEventTypeEnum::Achievement, '2003-02-27', 'Barry opens a second practice', [self::BARRY], 'Farber orthodontics, Manhattan', 'A waiting room with a view of the river.'),
            self::event(LifeEventTypeEnum::Message, '2002-05-30', 'Barry sends a card for Emma', [self::BARRY, self::RACHEL], null, 'With a coupon for free braces, redeemable in twelve years.'),
            self::event(LifeEventTypeEnum::Decision, '2004-03-04', 'Barry and Mindy try again', [self::BARRY], 'Long Island', 'Couples counselling, and a second honeymoon, not in Aruba.'),

            self::event(LifeEventTypeEnum::Birth, '1969-02-16', 'Phoebe and Ursula are born', [self::PHOEBE, self::URSULA], 'A hospital, upstate New York', 'Twins, eleven minutes apart. Ursula has always said she was first.'),
            self::event(LifeEventTypeEnum::Separation, '1983-06-01', 'Phoebe and Ursula stop speaking', [self::PHOEBE, self::URSULA], 'New York', 'After their mother died, over a lamp, which Ursula kept.'),
            self::event(LifeEventTypeEnum::NewJob, '1993-08-02', 'Ursula starts waitressing at Riff’s', [self::URSULA], 'Riff’s, New York', 'She has never once brought anybody what they ordered.'),
            self::event(LifeEventTypeEnum::Visit, '1995-02-23', 'Phoebe finds Ursula at Riff’s', [self::PHOEBE, self::URSULA, self::JOEY], 'Riff’s, New York', 'Joey thought he had seen Phoebe working in another restaurant.'),
            self::event(LifeEventTypeEnum::TimeTogether, '1995-03-09', 'Joey dates Ursula', [self::URSULA, self::JOEY], 'New York', 'She broke his heart without noticing she had.'),
            self::event(LifeEventTypeEnum::Argument, '1995-03-16', 'Phoebe and Ursula fight over Joey', [self::PHOEBE, self::URSULA], 'Riff’s, New York', 'Ursula did not see what the problem was.'),
            self::event(LifeEventTypeEnum::Visit, '1995-06-15', 'Ursula steals Phoebe’s identity, a little', [self::URSULA, self::PHOEBE], 'New York', 'She signed up for things in Phoebe’s name. Phoebe got the bills.'),
            self::event(LifeEventTypeEnum::NewJob, '1998-11-12', 'Ursula acts in films under Phoebe’s name', [self::URSULA, self::PHOEBE, self::JOEY, self::CHANDLER], 'New York', 'Joey and Chandler found the films. Phoebe collected the cheques.'),
            self::event(LifeEventTypeEnum::Decision, '2000-10-12', 'Ursula gets engaged to Eric', [self::URSULA], 'New York', 'He thought she was a teacher called Phoebe.'),
            self::event(LifeEventTypeEnum::Visit, '2000-10-19', 'Phoebe tells Eric the truth', [self::PHOEBE, self::URSULA], self::CENTRAL_PERK, 'He left Ursula. He did not stay with Phoebe either.'),
            self::event(LifeEventTypeEnum::Holiday, '2001-06-21', 'Ursula in Mexico', [self::URSULA], 'Cancún', 'She came back with somebody else’s passport.'),
            self::event(LifeEventTypeEnum::Interview, '2001-11-08', 'Ursula auditions for a soap', [self::URSULA, self::JOEY], 'NBC Studios, New York', 'She got the part Joey wanted, then quit on the first day.'),
            self::event(LifeEventTypeEnum::Message, '2002-02-14', 'Ursula sends Phoebe a birthday card', [self::URSULA, self::PHOEBE], null, 'Two days early and addressed to the wrong apartment.'),
            self::event(LifeEventTypeEnum::Game, '2002-08-01', 'Ursula at the racetrack', [self::URSULA], 'Belmont Park, New York', 'She won, then bet it all again, then lost.'),
            self::event(LifeEventTypeEnum::Visit, '2003-02-20', 'Ursula sells Phoebe’s birth certificate', [self::URSULA, self::PHOEBE], 'New York', 'For five hundred dollars. Phoebe bought it back.'),
            self::event(LifeEventTypeEnum::Move, '2003-06-12', 'Ursula moves to Los Angeles', [self::URSULA], 'Los Angeles', 'For acting, she said.'),
            self::event(LifeEventTypeEnum::Call, '2004-02-10', 'Ursula calls about Phoebe’s wedding', [self::URSULA, self::PHOEBE], null, 'To say she would not come, which nobody had asked.'),
            self::event(LifeEventTypeEnum::Reflection, '2004-02-19', 'Ursula thinks about her sister', [self::URSULA], 'Los Angeles', 'She kept the newspaper photograph of the wedding in the snow.'),
            self::event(LifeEventTypeEnum::Hobby, '1999-04-08', 'Ursula takes up tarot', [self::URSULA], 'New York', 'She reads cards for tips at Riff’s, and makes the futures up.'),
            self::event(LifeEventTypeEnum::Visit, '2002-04-11', 'Ursula turns up at Central Perk', [self::URSULA, self::PHOEBE, self::RACHEL, self::GUNTHER], self::CENTRAL_PERK, 'Gunther served her as if she were Phoebe. She let him.'),

            self::event(LifeEventTypeEnum::Birth, '1978-05-12', 'Frank Jr. is born', [self::FRANK], 'A hospital, upstate New York', 'Phoebe’s father’s son, with another woman. Phoebe did not know for eighteen years.'),
            self::event(LifeEventTypeEnum::SchoolClass, '1995-09-11', 'Frank Jr. takes home economics', [self::FRANK], 'A high school, upstate New York', 'Taught by a woman called Alice Knight.'),
            self::event(LifeEventTypeEnum::Hobby, '1996-06-06', 'Frank Jr. melts things', [self::FRANK], 'His mother’s garage, upstate New York', 'Plastic, mostly. It is how he relaxes.'),
            self::event(LifeEventTypeEnum::Visit, '1996-11-14', 'Frank Jr. comes to New York', [self::FRANK, self::PHOEBE, self::JOEY], self::CENTRAL_PERK, 'He wanted to see the city and meet women. Joey showed him around.'),
            self::event(LifeEventTypeEnum::Marriage, '1997-09-25', 'Frank Jr. marries Alice', [self::FRANK, self::PHOEBE], 'City hall, New York', 'She is forty four, he is eighteen, and they are very happy.'),
            self::event(LifeEventTypeEnum::Decision, '1997-10-09', 'Frank Jr. and Alice ask Phoebe to carry their baby', [self::FRANK, self::PHOEBE], self::CENTRAL_PERK, 'She said yes before they had finished the question.'),
            self::event(LifeEventTypeEnum::Memory, '1997-12-11', 'Three heartbeats', [self::FRANK, self::PHOEBE], 'A fertility clinic, New York', 'Not one baby. Three.'),
            self::event(LifeEventTypeEnum::NewJob, '1998-01-08', 'Frank Jr. takes a second job', [self::FRANK], 'A refrigerator repair shop', 'Three babies need three of everything.'),
            self::event(LifeEventTypeEnum::Reflection, '1998-10-15', 'Phoebe says goodbye to the triplets', [self::PHOEBE, self::FRANK], 'The hospital, New York', 'She asked to keep one, and then she let them go.'),
            self::event(LifeEventTypeEnum::Visit, '1999-10-21', 'Phoebe visits the triplets', [self::PHOEBE, self::FRANK], 'Frank Jr.’s house, upstate New York', 'Chandler, the girl, called her Aunt Phoebe.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2000-10-05', 'Phoebe babysits the triplets', [self::PHOEBE, self::FRANK], 'Frank Jr.’s house, upstate New York', 'Three toddlers, one Phoebe. She played Smelly Cat on repeat.'),
            self::event(LifeEventTypeEnum::MajorPurchase, '2001-03-29', 'Frank Jr. buys a minivan', [self::FRANK], 'A car dealership, upstate New York', 'Three car seats in a row.'),
            self::event(LifeEventTypeEnum::SchoolClass, '2002-09-05', 'The triplets start preschool', [self::FRANK], 'A preschool, upstate New York', 'Frank Jr. cried more than they did.'),
            self::event(LifeEventTypeEnum::Visit, '2003-01-23', 'Frank Jr. meets Mike', [self::FRANK, self::PHOEBE, self::MIKE], self::CENTRAL_PERK, 'Mike asked what he did, and Frank Jr. said he melted things.'),
            self::event(LifeEventTypeEnum::Holiday, '2003-08-21', 'The Buffays at the lake', [self::FRANK, self::PHOEBE, self::MIKE], 'A lake, upstate New York', 'The triplets, a canoe, and nobody fell in.'),
            self::event(LifeEventTypeEnum::Visit, '2004-02-12', 'Frank Jr. at Phoebe’s wedding', [self::FRANK, self::PHOEBE, self::MIKE], 'The street outside Central Perk', 'He brought the triplets, who threw snow instead of rice.'),

            self::event(LifeEventTypeEnum::NewJob, '1994-01-10', 'Gunther starts at Central Perk', [self::GUNTHER], self::CENTRAL_PERK, 'After his character on a soap opera was killed off in an avalanche.'),
            self::event(LifeEventTypeEnum::Training, '1994-10-06', 'Gunther trains Rachel', [self::GUNTHER, self::RACHEL], self::CENTRAL_PERK, 'He explained the milk steamer three times and she broke it once.'),
            self::event(LifeEventTypeEnum::HomeImprovement, '1995-01-19', 'Gunther bleaches his hair', [self::GUNTHER], 'New York', 'Blinding white, and he never went back.'),
            self::event(LifeEventTypeEnum::Decision, '1996-12-19', 'Gunther hires Rachel’s replacement', [self::GUNTHER], self::CENTRAL_PERK, 'He chose the one who reminded him least of her.'),
            self::event(LifeEventTypeEnum::Achievement, '1997-11-13', 'Gunther becomes manager', [self::GUNTHER, self::RACHEL], self::CENTRAL_PERK, 'Terry retired, and Gunther took the office and the keys.'),
            self::event(LifeEventTypeEnum::HomeImprovement, '1998-07-16', 'Central Perk gets a new couch', [self::GUNTHER], self::CENTRAL_PERK, 'He kept the orange one. Nobody else was allowed to sit there.'),
            self::event(LifeEventTypeEnum::Concert, '2000-02-17', 'Smelly Cat, the music video', [self::PHOEBE, self::GUNTHER], self::CENTRAL_PERK, 'Phoebe found out the voice on the tape was not hers.'),
            self::event(LifeEventTypeEnum::Concert, '2004-01-15', 'Mike plays the piano at Central Perk', [self::MIKE, self::PHOEBE, self::GUNTHER], self::CENTRAL_PERK, 'Gunther let him in on a Thursday. It became every Thursday.'),
            self::event(LifeEventTypeEnum::Holiday, '2002-08-08', 'Gunther in Holland', [self::GUNTHER], 'Amsterdam', 'His first holiday in eight years. He sent Rachel a postcard.'),
            self::event(LifeEventTypeEnum::Memory, '2004-05-06', 'The last coffee at Central Perk', [self::GUNTHER, self::MONICA, self::RACHEL, self::ROSS, self::CHANDLER, self::JOEY, self::PHOEBE], self::CENTRAL_PERK, 'Everybody put their keys on the counter and went for coffee.'),
            self::event(LifeEventTypeEnum::Argument, '1998-12-10', 'Gunther fires Joey', [self::GUNTHER, self::JOEY], self::CENTRAL_PERK, 'Joey let a customer have a muffin for nothing. Again.'),
            self::event(LifeEventTypeEnum::Interview, '2001-09-20', 'Gunther auditions for a soap', [self::GUNTHER, self::JOEY], 'NBC Studios, New York', 'He did not get the part. Joey did not tell him he had been up for it too.'),
            self::event(LifeEventTypeEnum::Reflection, '2003-03-27', 'Gunther counts the years', [self::GUNTHER], self::CENTRAL_PERK, 'Nine years pouring Rachel’s coffee, and not one word said.'),

            self::event(LifeEventTypeEnum::Decision, '2001-04-05', 'Mike quits the law', [self::MIKE], 'A law firm, New York', 'Six years of law and he wanted to play the piano.'),
            self::event(LifeEventTypeEnum::Meal, '2002-11-07', 'Phoebe meets Mike’s parents', [self::PHOEBE, self::MIKE], 'The Hannigan house, New York', 'They were stiff, rich and horrified. Phoebe told them about her mother.'),
            self::event(LifeEventTypeEnum::Separation, '2003-01-30', 'Phoebe and Mike break up', [self::PHOEBE, self::MIKE], 'A restaurant, New York', 'He never wanted to marry again. She did.'),
            self::event(LifeEventTypeEnum::Decision, '2003-05-15', 'Mike follows Phoebe to Barbados', [self::MIKE, self::PHOEBE], 'Barbados', 'He flew out without telling her, and got there in the middle of her date.'),
            self::event(LifeEventTypeEnum::Game, '2003-11-20', 'Mike and Joey play foosball', [self::MIKE, self::JOEY, self::CHANDLER], self::APARTMENT_19, 'Mike lost the first game and won the next eleven.'),
            self::event(LifeEventTypeEnum::Meal, '2003-11-27', 'Mike’s first Thanksgiving with everybody', [self::MIKE, self::PHOEBE, self::MONICA, self::CHANDLER, self::ROSS, self::RACHEL, self::JOEY], self::APARTMENT_20, 'Monica made eight kinds of potato and the dog got into the turkey.'),
            self::event(LifeEventTypeEnum::Hobby, '2004-03-18', 'Mike joins a band', [self::MIKE], 'A bar, New York', 'Mostly jazz, once a month.'),
            self::event(LifeEventTypeEnum::Trip, '2004-02-19', 'Phoebe and Mike’s honeymoon', [self::PHOEBE, self::MIKE], 'Barbados', 'Back to where he had found her. It did not rain this time.'),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function mrHeckles(): array
    {
        return [
            self::event(LifeEventTypeEnum::Graduation, '1942-06-12', 'Mr. Heckles graduates from high school', [self::HECKLES], 'A high school, New York', 'The yearbook said he was funny once. Chandler found it after he died.'),
            self::event(LifeEventTypeEnum::Move, '1958-04-01', 'Mr. Heckles moves into 90 Bedford Street', [self::HECKLES], '90 Bedford Street, New York', 'The apartment below 20, where he stayed for thirty seven years.'),
            self::event(LifeEventTypeEnum::Hobby, '1975-05-15', 'Mr. Heckles takes up the clarinet', [self::HECKLES], '90 Bedford Street, New York', 'He practised until the neighbours complained, which he enjoyed.'),
            self::event(LifeEventTypeEnum::Argument, '1994-09-29', 'Mr. Heckles complains about the noise', [self::HECKLES, self::MONICA, self::RACHEL], '90 Bedford Street, New York', 'We were walking on the floor, he said, loudly.'),
            self::event(LifeEventTypeEnum::HouseholdTask, '1994-10-13', 'Mr. Heckles bangs on the ceiling with a broom', [self::HECKLES, self::MONICA], '90 Bedford Street, New York', 'At eleven in the morning. We were making toast.'),
            self::event(LifeEventTypeEnum::Visit, '1994-10-27', 'Mr. Heckles claims the girls’ furniture', [self::HECKLES, self::MONICA, self::RACHEL], self::APARTMENT_20, 'He said the table had been his. It had not.'),
            self::event(LifeEventTypeEnum::Argument, '1994-11-10', 'Mr. Heckles and Joey fall out', [self::HECKLES, self::JOEY], '90 Bedford Street, New York', 'Joey had apparently been “stomping like a herd of elephants”.'),
            self::event(LifeEventTypeEnum::Visit, '1994-11-24', 'Mr. Heckles wants the Thanksgiving leftovers', [self::HECKLES, self::MONICA, self::CHANDLER], self::APARTMENT_20, 'He said the smell had come through his ceiling, so he was owed some.'),
            self::event(LifeEventTypeEnum::Argument, '1994-12-08', 'Mr. Heckles and the Christmas lights', [self::HECKLES, self::RACHEL], '90 Bedford Street, New York', 'Our lights were keeping him awake. They were in the window across the courtyard.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '1995-01-12', 'Mr. Heckles sees his doctor', [self::HECKLES], 'A clinic, New York', 'His heart, the doctor said. He said the doctor was too loud.'),
            self::event(LifeEventTypeEnum::Visit, '1995-02-02', 'Mr. Heckles borrows a cup of sugar', [self::HECKLES, self::MONICA], self::APARTMENT_20, 'He brought his own cup, and took two.'),
            self::event(LifeEventTypeEnum::Argument, '1995-02-16', 'Mr. Heckles and Chandler’s laugh', [self::HECKLES, self::CHANDLER], '90 Bedford Street, New York', 'He said he could hear Chandler making jokes through two floors.'),
            self::event(LifeEventTypeEnum::Meeting, '1995-03-09', 'Mr. Heckles at the tenants’ meeting', [self::HECKLES, self::MONICA, self::JOEY], '90 Bedford Street, New York', 'He proposed a ban on shoes above the fourth floor.'),
            self::event(LifeEventTypeEnum::Argument, '1995-04-20', 'Mr. Heckles and the girls’ party', [self::HECKLES, self::MONICA, self::RACHEL, self::PHOEBE], self::APARTMENT_20, 'He came up in a dressing gown to say he was planning to sleep.'),
            self::event(LifeEventTypeEnum::Illness, '1995-06-01', 'Mr. Heckles falls ill', [self::HECKLES], '90 Bedford Street, New York', 'Nobody knew, because he told nobody.'),
            self::event(LifeEventTypeEnum::Visit, '1995-06-22', 'Mr. Heckles claims the chick and the duck', [self::HECKLES, self::CHANDLER, self::JOEY], self::APARTMENT_19, 'He said the chick was his and he wanted it back. He had never seen it before.'),
            self::event(LifeEventTypeEnum::HospitalStay, '1995-07-20', 'Mr. Heckles in hospital', [self::HECKLES], 'St. Vincent’s Hospital, New York', 'A week, alone. He complained about the nurses.'),
            self::event(LifeEventTypeEnum::Reflection, '1995-08-10', 'Mr. Heckles writes his will', [self::HECKLES], '90 Bedford Street, New York', 'He left everything to the girls upstairs, for being noisy.'),
            self::event(LifeEventTypeEnum::Argument, '1995-09-28', 'Mr. Heckles bangs on the ceiling, the last time', [self::HECKLES, self::MONICA, self::RACHEL], '90 Bedford Street, New York', 'We were trying to sleep, we said. So was he.'),
            self::event(LifeEventTypeEnum::OtherLifeEvent, '1995-10-05', 'Mr. Heckles dies', [self::HECKLES, self::MONICA, self::RACHEL, self::CHANDLER, self::JOEY], '90 Bedford Street, New York', 'Banging his broom on the ceiling. He left us everything, which was mostly junk.'),
            self::event(LifeEventTypeEnum::Reflection, '1995-10-05', 'Chandler finds himself in Mr. Heckles’s yearbook', [self::CHANDLER], '90 Bedford Street, New York', 'A funny man who ended up alone. Chandler called Janice that night.'),
        ];
    }

    /** @return list<array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}> */
    private function twins(): array
    {
        $twins = [self::JACK_BING, self::ERICA];
        $grandparents = [self::JACK, self::JUDY, self::NORA, self::HELENA];

        return [
            self::event(LifeEventTypeEnum::Birth, '2004-05-06', 'Jack and Erica are born', [...$twins, self::MONICA, self::CHANDLER], 'A hospital, New York', 'We were expecting one baby. The doctor said there was a second one coming.'),
            self::event(LifeEventTypeEnum::Move, '2004-05-06', 'Moving to Westchester', [...$twins, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'The apartment empty, the keys on the counter, and two car seats in the back.'),
            self::event(LifeEventTypeEnum::Visit, '2004-05-09', 'The grandparents meet the twins', [...$twins, ...$grandparents, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'Judy and Nora each held one, and neither would hand theirs over.'),
            self::event(LifeEventTypeEnum::MedicalAppointment, '2004-05-20', 'The twins’ first check up', [...$twins, self::MONICA, self::CHANDLER], 'A pediatrician, Westchester', 'Both healthy. Erica screamed, Jack slept through it.'),
            self::event(LifeEventTypeEnum::Visit, '2004-06-12', 'Ross, Rachel and Emma visit Westchester', [...$twins, self::ROSS, self::RACHEL, self::EMMA, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'Emma poked Jack in the eye, gently, to see what he would do.'),
            self::event(LifeEventTypeEnum::Visit, '2004-07-04', 'Joey brings the chick and the duck to meet the twins', [...$twins, self::JOEY, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'The duck was not invited. It came anyway.'),
            self::event(LifeEventTypeEnum::Decision, '2004-11-04', 'The adoption is finalised', [...$twins, self::MONICA, self::CHANDLER], 'A family court, Ohio', 'The judge smiled at the twins and signed.'),
            self::event(LifeEventTypeEnum::Meal, '2004-11-25', 'The first Thanksgiving in Westchester', [...$twins, self::MONICA, self::CHANDLER, self::ROSS, self::RACHEL, self::EMMA, self::BEN, self::JOEY, self::PHOEBE, self::MIKE, self::JACK, self::JUDY], self::WESTCHESTER, 'Monica cooked three turkeys, one of them for Joey.'),
            self::event(LifeEventTypeEnum::Holiday, '2004-12-25', 'The twins’ first Christmas', [...$twins, ...$grandparents, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'Helena and Nora in the same room, being civil, for the children.'),
            self::event(LifeEventTypeEnum::Meal, '2005-11-24', 'Thanksgiving with both of Chandler’s parents', [...$twins, self::NORA, self::HELENA, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'Chandler ate turkey for the first time since 1978.'),
            self::event(LifeEventTypeEnum::Exercise, '2005-04-14', 'Erica takes her first steps', [...$twins, self::MONICA, self::CHANDLER], self::WESTCHESTER, 'Before Jack, and she has not let him forget it.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2005-05-06', 'The twins’ first birthday', [...$twins, ...$grandparents, self::MONICA, self::CHANDLER, self::ROSS, self::RACHEL, self::EMMA, self::BEN, self::CAROL, self::SUSAN, self::JOEY, self::PHOEBE, self::MIKE], self::WESTCHESTER, 'Everybody came. Two cakes, both destroyed.'),
            self::event(LifeEventTypeEnum::Visit, '2006-04-20', 'The triplets come to play', [...$twins, self::PHOEBE, self::MIKE, self::FRANK], self::WESTCHESTER, 'Five children in one yard. The swing set did not survive.'),
            self::event(LifeEventTypeEnum::TimeTogether, '2006-05-06', 'The twins’ second birthday', [...$twins, self::MONICA, self::CHANDLER, self::JACK, self::JUDY, self::EMMA, self::BEN, self::ROSS, self::RACHEL, self::CAROL, self::SUSAN], self::WESTCHESTER, 'A bouncy castle. Ross was asked to leave it.'),
            self::event(LifeEventTypeEnum::Trip, '2006-08-17', 'The twins visit Grandma Helena in Las Vegas', [...$twins, self::HELENA, self::CHANDLER, self::MONICA], 'Las Vegas', 'Helena had a matinee put on just for them.'),
            self::event(LifeEventTypeEnum::Reading, '2006-12-14', 'Grandma Nora reads to the twins', [...$twins, self::NORA], self::WESTCHESTER, 'A children’s book she had written for them. Chandler read it first, to be sure.'),
            self::event(LifeEventTypeEnum::Holiday, '2007-07-12', 'The family holiday in Montauk', [...$twins, self::MONICA, self::CHANDLER, self::ROSS, self::RACHEL, self::EMMA, self::BEN, self::CAROL, self::SUSAN], 'Montauk, New York', 'The same beach house, ten years on. Nobody got stung.'),
            self::event(LifeEventTypeEnum::SchoolClass, '2007-09-05', 'The twins start preschool', [...$twins, self::MONICA, self::CHANDLER], 'A preschool, Westchester', 'Monica labelled everything. Chandler cried in the car.'),
            self::event(LifeEventTypeEnum::Illness, '2008-02-14', 'The twins catch chicken pox', [...$twins, self::MONICA, self::PHOEBE], self::WESTCHESTER, 'Both at once, on Valentine’s Day. Phoebe brought the oatmeal.'),
            self::event(LifeEventTypeEnum::Excursion, '2008-04-10', 'Uncle Ross takes the twins to the museum', [...$twins, self::ROSS, self::EMMA, self::BEN, self::CAROL], 'Museum of Prehistoric History, New York', 'The full tour, twice, with the Latin names.'),
            self::event(LifeEventTypeEnum::Exercise, '2008-07-17', 'Swimming lessons', [...$twins, self::MONICA, self::JOEY, self::EMMA], 'A pool, Westchester', 'Joey offered to teach them. Monica hired somebody.'),
            self::event(LifeEventTypeEnum::HospitalStay, '2009-06-11', 'Erica breaks her arm', [...$twins, self::MONICA, self::CHANDLER], 'A hospital, Westchester', 'Off the climbing frame, trying to beat Jack to the top. One night in hospital.'),
            self::event(LifeEventTypeEnum::SchoolClass, '2009-09-08', 'The twins start kindergarten', [...$twins, self::MONICA, self::CHANDLER, self::JACK, self::JUDY], 'An elementary school, Westchester', 'Jack and Judy drove up to see them go in.'),
            self::event(LifeEventTypeEnum::Hike, '2010-05-15', 'A walk in the Hudson Highlands', [...$twins, self::MONICA, self::CHANDLER, self::BEN, self::EMMA, self::ROSS, self::SUSAN], 'Hudson Highlands, New York', 'Six miles, and Chandler carried both twins for the last two.'),
        ];
    }

    /**
     * @param  list<string>  $participants
     * @return array{type: LifeEventTypeEnum, date: string, title: string, participants: list<string>, location: ?string, narrative: string}
     */
    private static function event(LifeEventTypeEnum $type, string $date, string $title, array $participants, ?string $location, string $narrative): array
    {
        return ['type' => $type, 'date' => $date, 'title' => $title, 'participants' => $participants, 'location' => $location, 'narrative' => $narrative];
    }
}
